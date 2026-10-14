<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReversalService
{
    public function __construct(
        protected SequenceService $sequences,
    ) {}

    /**
     * Membuat jurnal pembalik atas transaksi yang telah tersimpan.
     * Saldo rekening dipulihkan dan transaksi asal ditandai "reversed".
     */
    public function reverse(Transaction $original, User $supervisor, string $reason): Transaction
    {
        return DB::transaction(function () use ($original, $supervisor, $reason) {
            $this->assertReportNotApproved($original);

            $transaction = Transaction::query()->lockForUpdate()->findOrFail($original->id);

            if ($transaction->isReversal()) {
                throw new TransactionValidationException('Transaksi koreksi tidak dapat dikoreksi ulang.');
            }

            if ($transaction->status !== 'success') {
                throw new TransactionValidationException('Hanya transaksi berstatus sukses yang dapat dikoreksi.');
            }

            if (! in_array($transaction->type, ['deposit', 'withdrawal', 'transfer'], true)) {
                throw new TransactionValidationException('Jenis transaksi ini tidak dapat dikoreksi.');
            }

            $accountIds = array_filter([$transaction->account_id, $transaction->recipient_account_id]);
            sort($accountIds);

            Account::query()->lockForUpdate()->whereIn('id', $accountIds)->get();

            $account = Account::query()->findOrFail($transaction->account_id);
            $recipient = $transaction->recipient_account_id !== null
                ? Account::query()->findOrFail($transaction->recipient_account_id)
                : null;

            $amount = (int) $transaction->amount;
            $balanceBefore = (int) $account->balance;

            $balanceAfter = match ($transaction->type) {
                'deposit' => $this->reverseDeposit($account, $amount),
                'withdrawal' => $this->reverseWithdrawal($account, $amount),
                default => $this->reverseTransfer($account, $recipient, $amount),
            };

            $reversal = Transaction::create([
                'account_id' => $account->id,
                'recipient_account_id' => $transaction->recipient_account_id,
                'teller_id' => $transaction->teller_id,
                'type' => 'reversal',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'receipt_number' => $this->generateNumber(),
                'slip_number' => null,
                'notes' => $reason,
                'reversal_of_transaction_id' => $transaction->id,
                'status' => 'success',
                'transaction_at' => now(),
            ]);

            $this->createReversalJournalEntries($reversal, $transaction);

            $transaction->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => $supervisor->id,
                'reversal_reason' => $reason,
            ]);

            AuditLogService::record('transaction_reversed', 'transaction', $transaction->id, [
                'reversal_receipt_number' => $reversal->receipt_number,
                'account_number' => $account->account_number,
                'amount' => $amount,
                'reason' => $reason,
            ], $supervisor->id);

            return $reversal;
        });
    }

    protected function reverseWithdrawal(Account $account, int $amount): int
    {
        $balanceAfter = (int) $account->balance + $amount;

        $account->update(['balance' => $balanceAfter]);

        return $balanceAfter;
    }

    protected function reverseDeposit(Account $account, int $amount): int
    {
        $balanceBefore = (int) $account->balance;

        if ($balanceBefore < $amount) {
            throw new TransactionValidationException(
                'Saldo rekening tidak mencukupi untuk membatalkan setoran ini (saldo saat ini Rp '.number_format($balanceBefore).').'
            );
        }

        $balanceAfter = $balanceBefore - $amount;
        $account->update(['balance' => $balanceAfter]);

        return $balanceAfter;
    }

    protected function reverseTransfer(Account $account, ?Account $recipient, int $amount): int
    {
        if ($recipient === null) {
            throw new TransactionValidationException('Rekening tujuan transfer tidak ditemukan.');
        }

        $recipientBalance = (int) $recipient->balance;

        if ($recipientBalance < $amount) {
            throw new TransactionValidationException(
                'Saldo rekening penerima tidak mencukupi untuk membatalkan transfer ini (saldo saat ini Rp '.number_format($recipientBalance).').'
            );
        }

        $balanceAfter = (int) $account->balance + $amount;

        $account->update(['balance' => $balanceAfter]);
        $recipient->update(['balance' => $recipientBalance - $amount]);

        return $balanceAfter;
    }

    protected function createReversalJournalEntries(Transaction $reversal, Transaction $original): void
    {
        $amount = (int) $original->amount;

        $rows = match ($original->type) {
            'deposit' => [
                ['account_code' => TransactionService::ACCOUNT_CODE_SAVINGS, 'entry_type' => 'debit', 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => 'Koreksi setoran (jurnal pembalik)'],
                ['account_code' => TransactionService::ACCOUNT_CODE_CASH, 'entry_type' => 'credit', 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => 'Koreksi setoran (jurnal pembalik)'],
            ],
            'withdrawal' => [
                ['account_code' => TransactionService::ACCOUNT_CODE_CASH, 'entry_type' => 'debit', 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => 'Koreksi penarikan (jurnal pembalik)'],
                ['account_code' => TransactionService::ACCOUNT_CODE_SAVINGS, 'entry_type' => 'credit', 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => 'Koreksi penarikan (jurnal pembalik)'],
            ],
            default => [
                ['account_code' => TransactionService::ACCOUNT_CODE_SAVINGS, 'entry_type' => 'debit', 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => 'Koreksi transfer masuk ke pengirim'],
                ['account_code' => TransactionService::ACCOUNT_CODE_SAVINGS, 'entry_type' => 'credit', 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => 'Koreksi transfer keluar dari penerima'],
            ],
        };

        foreach ($rows as $index => $row) {
            JurnalAkuntansi::create([
                'transaction_id' => $reversal->id,
                'cash_transfer_id' => null,
                'journal_number' => $reversal->receipt_number.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'account_code' => $row['account_code'],
                'entry_type' => $row['entry_type'],
                'amount' => $row['debit_amount'] > 0 ? $row['debit_amount'] : $row['credit_amount'],
                'debit_amount' => $row['debit_amount'],
                'credit_amount' => $row['credit_amount'],
                'description' => $row['description'],
                'status' => 'posted',
                'journal_at' => now(),
            ]);
        }
    }

    protected function assertReportNotApproved(Transaction $transaction): void
    {
        $reports = DailyReport::query()
            ->where('teller_id', $transaction->teller_id)
            ->whereDate('report_date', $transaction->transaction_at->toDateString())
            ->whereIn('status', ['submitted', 'approved'])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($reports->contains('status', 'approved')) {
            throw new TransactionValidationException(
                'Transaksi tidak dapat dikoreksi karena laporan harian tanggal tersebut telah disetujui dan kas telah dipindahkan ke brankas.'
            );
        }
    }

    protected function generateNumber(): string
    {
        $key = 'REV-'.now()->format('Ymd');

        return $this->sequences->formatted($key, $key);
    }
}
