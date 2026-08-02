<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\JurnalAkuntansi;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TransactionService
{
    public const ACCOUNT_CODE_CASH = '101';

    public const ACCOUNT_CODE_SAVINGS = '201';

    public function deposit(Account $account, array $data, User $teller): Transaction
    {
        return DB::transaction(function () use ($account, $data, $teller) {
            $locked = Account::query()->lockForUpdate()->findOrFail($account->id);
            $this->assertAccountActive($locked);

            $amount = (int) ($data['amount'] ?? $data['slip_amount'] ?? 0);
            if ($amount <= 0) {
                throw new TransactionValidationException('Nominal harus lebih besar dari nol.');
            }

            $balanceBefore = (int) $locked->balance;
            $balanceAfter = $balanceBefore + $amount;

            $transaction = $this->createTransaction(
                $locked,
                $teller,
                'deposit',
                $amount,
                $balanceBefore,
                $balanceAfter,
                $data,
            );

            $locked->update(['balance' => $balanceAfter]);

            $this->createJournalEntries($transaction, 'deposit', $amount);
            $this->assertJournalBalanced($transaction);

            AuditLogService::record('transaction_deposit_success', 'transaction', $transaction->id, [
                'account_number' => $locked->account_number,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
            ], $teller->id);

            return $transaction;
        });
    }

    public function withdraw(Account $account, array $data, string $pin, User $teller): Transaction
    {
        return DB::transaction(function () use ($account, $data, $pin, $teller) {
            $locked = Account::query()->lockForUpdate()->findOrFail($account->id);
            $this->assertAccountActive($locked);

            $customer = $locked->customer;
            if (! $customer || ! Hash::check($pin, $customer->pin_hash)) {
                throw new TransactionValidationException('PIN nasabah salah.');
            }

            $amount = (int) ($data['amount'] ?? 0);
            if ($amount <= 0) {
                throw new TransactionValidationException('Nominal harus lebih besar dari nol.');
            }

            $minimumBalance = (int) (SystemConfig::find('minimum_balance')?->config_value ?? 10000);

            $balanceBefore = (int) $locked->balance;
            if ($amount > $balanceBefore) {
                throw new TransactionValidationException('Saldo tidak mencukupi.');
            }

            $balanceAfter = $balanceBefore - $amount;
            if ($balanceAfter < $minimumBalance) {
                throw new TransactionValidationException(
                    'Saldo setelah penarikan tidak boleh kurang dari Rp '.number_format($minimumBalance).'.'
                );
            }

            $transaction = $this->createTransaction(
                $locked,
                $teller,
                'withdrawal',
                $amount,
                $balanceBefore,
                $balanceAfter,
                $data,
            );

            $locked->update(['balance' => $balanceAfter]);

            $this->createJournalEntries($transaction, 'withdrawal', $amount);
            $this->assertJournalBalanced($transaction);

            AuditLogService::record('transaction_withdrawal_success', 'transaction', $transaction->id, [
                'account_number' => $locked->account_number,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
            ], $teller->id);

            return $transaction;
        });
    }

    protected function createTransaction(
        Account $account,
        User $teller,
        string $type,
        int $amount,
        int $balanceBefore,
        int $balanceAfter,
        array $data,
    ): Transaction {
        return Transaction::create([
            'account_id' => $account->id,
            'teller_id' => $teller->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'receipt_number' => $this->generateTransactionNumber(),
            'slip_number' => $data['slip_number'] ?? null,
            'slip_amount' => isset($data['slip_amount']) ? (int) $data['slip_amount'] : null,
            'physical_cash_amount' => isset($data['physical_cash_amount']) ? (int) $data['physical_cash_amount'] : null,
            'status' => 'success',
            'transaction_at' => now(),
        ]);
    }

    protected function createJournalEntries(Transaction $transaction, string $type, int $amount): void
    {
        $rows = match ($type) {
            'deposit' => [
                ['account_code' => self::ACCOUNT_CODE_CASH, 'entry_type' => 'debit', 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => 'Setoran tunai'],
                ['account_code' => self::ACCOUNT_CODE_SAVINGS, 'entry_type' => 'credit', 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => 'Setoran tunai'],
            ],
            default => [
                ['account_code' => self::ACCOUNT_CODE_SAVINGS, 'entry_type' => 'debit', 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => 'Penarikan tunai'],
                ['account_code' => self::ACCOUNT_CODE_CASH, 'entry_type' => 'credit', 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => 'Penarikan tunai'],
            ],
        };

        foreach ($rows as $index => $row) {
            JurnalAkuntansi::create([
                'transaction_id' => $transaction->id,
                'journal_number' => $transaction->receipt_number.'-'.str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'account_code' => $row['account_code'],
                'entry_type' => $row['entry_type'],
                'amount' => $amount,
                'debit_amount' => $row['debit_amount'],
                'credit_amount' => $row['credit_amount'],
                'description' => $row['description'],
                'status' => 'posted',
                'journal_at' => now(),
            ]);
        }
    }

    protected function assertJournalBalanced(Transaction $transaction): void
    {
        $totalDebit = (int) JurnalAkuntansi::where('transaction_id', $transaction->id)->sum('debit_amount');
        $totalCredit = (int) JurnalAkuntansi::where('transaction_id', $transaction->id)->sum('credit_amount');

        if ($totalDebit !== $totalCredit) {
            throw new TransactionValidationException('Jurnal akuntansi tidak seimbang.');
        }
    }

    protected function assertAccountActive(Account $account): void
    {
        if ($account->status !== 'active') {
            throw new TransactionValidationException('Rekening tidak aktif.');
        }
    }

    protected function generateTransactionNumber(): string
    {
        $prefix = 'TRX-'.now()->format('Ymd');
        $count = (int) Transaction::where('transaction_at', '>=', now()->startOfDay())->count();

        return $prefix.'-'.str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
