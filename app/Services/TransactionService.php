<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\Customer;
use App\Models\JurnalAkuntansi;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TransactionService
{
    public function __construct(
        protected SequenceService $sequences,
    ) {}

    public const ACCOUNT_CODE_CASH = AccountingService::ACCOUNT_CODE_CASH_TELLER;

    public const ACCOUNT_CODE_VAULT = AccountingService::ACCOUNT_CODE_CASH_VAULT;

    public const ACCOUNT_CODE_SAVINGS = AccountingService::ACCOUNT_CODE_SAVINGS;

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
        $this->assertAccountActive($account);

        $customer = $account->customer()->first();
        if (! $customer) {
            throw new TransactionValidationException('Data nasabah tidak ditemukan.');
        }

        $this->verifyCustomerPin($customer, $pin, 'penarikan', $teller->id);

        return DB::transaction(function () use ($account, $data, $teller) {
            $locked = Account::query()->lockForUpdate()->findOrFail($account->id);
            $this->assertAccountActive($locked);

            $amount = (int) ($data['amount'] ?? 0);
            if ($amount <= 0) {
                throw new TransactionValidationException('Nominal harus lebih besar dari nol.');
            }

            $config = SystemConfig::find('minimum_balance');
            $minimumBalance = (int) ($config->config_value ?? 10000);

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

    public function transfer(Account $source, Account $destination, array $data, string $pin, User $teller): Transaction
    {
        if ($source->id === $destination->id) {
            throw new TransactionValidationException('Rekening tujuan tidak boleh sama dengan rekening pengirim.');
        }

        $this->assertAccountActive($source);

        $customer = $source->customer()->first();
        if (! $customer) {
            throw new TransactionValidationException('Data nasabah pengirim tidak ditemukan.');
        }

        $this->verifyCustomerPin($customer, $pin, 'transfer', $teller->id);

        return DB::transaction(function () use ($source, $destination, $data, $teller) {
            // Deadlock prevention: lock accounts in ascending ID order
            $firstId = min($source->id, $destination->id);
            $secondId = max($source->id, $destination->id);

            Account::query()->lockForUpdate()->whereIn('id', [$firstId, $secondId])->get();
            $lockedSource = Account::findOrFail($source->id);
            $lockedDest = Account::findOrFail($destination->id);

            $this->assertAccountActive($lockedSource);
            $this->assertAccountActive($lockedDest);

            $amount = (int) ($data['amount'] ?? 0);
            if ($amount <= 0) {
                throw new TransactionValidationException('Nominal transfer harus lebih besar dari nol.');
            }

            $config = SystemConfig::find('minimum_balance');
            $minimumBalance = (int) ($config->config_value ?? 10000);

            $sourceBalanceBefore = (int) $lockedSource->balance;
            if ($amount > $sourceBalanceBefore) {
                throw new TransactionValidationException('Saldo rekening pengirim tidak mencukupi.');
            }

            $sourceBalanceAfter = $sourceBalanceBefore - $amount;
            if ($sourceBalanceAfter < $minimumBalance) {
                throw new TransactionValidationException(
                    'Saldo pengirim setelah transfer tidak boleh kurang dari Rp '.number_format($minimumBalance).'.'
                );
            }

            $destBalanceBefore = (int) $lockedDest->balance;
            $destBalanceAfter = $destBalanceBefore + $amount;

            $notes = $data['notes'] ?? 'Transfer ke Rek. '.$lockedDest->account_number;

            $transaction = Transaction::create([
                'account_id' => $lockedSource->id,
                'recipient_account_id' => $lockedDest->id,
                'teller_id' => $teller->id,
                'type' => 'transfer',
                'amount' => $amount,
                'balance_before' => $sourceBalanceBefore,
                'balance_after' => $sourceBalanceAfter,
                'receipt_number' => $this->generateTransactionNumber(),
                'slip_number' => $data['slip_number'] ?? null,
                'notes' => $notes,
                'status' => 'success',
                'transaction_at' => now(),
            ]);

            $lockedSource->update(['balance' => $sourceBalanceAfter]);
            $lockedDest->update(['balance' => $destBalanceAfter]);

            // Create transfer double-entry journal (Debet 201 Pengirim, Kredit 201 Penerima)
            $this->createTransferJournalEntries($transaction, $lockedSource, $lockedDest, $amount);
            $this->assertJournalBalanced($transaction);

            AuditLogService::record('transaction_transfer_success', 'transaction', $transaction->id, [
                'source_account' => $lockedSource->account_number,
                'destination_account' => $lockedDest->account_number,
                'amount' => $amount,
                'source_balance_after' => $sourceBalanceAfter,
                'dest_balance_after' => $destBalanceAfter,
            ], $teller->id);

            return $transaction;
        });
    }

    public function verifyCustomerPin(Customer $customer, string $pin, string $actionType = 'transaksi', ?int $actorId = null): void
    {
        $failure = null;

        DB::transaction(function () use ($customer, $pin, $actionType, $actorId, &$failure) {
            $locked = Customer::query()
                ->whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isPinLocked()) {
                $failure = new TransactionValidationException(
                    'PIN rekening terkunci sementara karena 3x salah. Silakan coba kembali setelah '.$locked->locked_until->diffForHumans().' atau lakukan reset PIN.'
                );

                return;
            }

            if (! Hash::check($pin, $locked->pin_hash)) {
                $attempts = (int) $locked->failed_pin_attempts + 1;

                if ($attempts >= 3) {
                    $locked->update([
                        'failed_pin_attempts' => $attempts,
                        'locked_until' => now()->addMinutes(15),
                    ]);

                    AuditLogService::record('customer_pin_locked', 'customer', $locked->id, [
                        'student_number' => $locked->student_number,
                        'action' => $actionType,
                        'failed_attempts' => $attempts,
                    ], $actorId);

                    $failure = new TransactionValidationException('PIN salah 3 kali berturut-turut. Rekening nasabah dikunci selama 15 menit.');

                    return;
                }

                $locked->update(['failed_pin_attempts' => $attempts]);

                $failure = new TransactionValidationException('PIN nasabah salah. (Percobaan ke-'.$attempts.' dari 3)');

                return;
            }

            // Reset failed attempts on success
            if ((int) $locked->failed_pin_attempts > 0 || $locked->locked_until !== null) {
                $locked->update([
                    'failed_pin_attempts' => 0,
                    'locked_until' => null,
                ]);
            }
        });

        if ($failure !== null) {
            throw $failure;
        }
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
            'notes' => $data['notes'] ?? null,
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
                'cash_transfer_id' => null,
                'journal_number' => $transaction->receipt_number.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
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

    protected function createTransferJournalEntries(Transaction $transaction, Account $source, Account $destination, int $amount): void
    {
        $rows = [
            [
                'account_code' => self::ACCOUNT_CODE_SAVINGS,
                'entry_type' => 'debit',
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'description' => 'Transfer keluar ke Rek. '.$destination->account_number.' ('.$destination->customer?->full_name.')',
            ],
            [
                'account_code' => self::ACCOUNT_CODE_SAVINGS,
                'entry_type' => 'credit',
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => 'Transfer masuk dari Rek. '.$source->account_number.' ('.$source->customer?->full_name.')',
            ],
        ];

        foreach ($rows as $index => $row) {
            JurnalAkuntansi::create([
                'transaction_id' => $transaction->id,
                'cash_transfer_id' => null,
                'journal_number' => $transaction->receipt_number.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
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
        $key = 'TRX-'.now()->format('Ymd');

        return $this->sequences->formatted($key, $key);
    }
}
