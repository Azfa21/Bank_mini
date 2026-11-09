<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawalBooking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class WithdrawalBookingService
{
    public const DAILY_LIMIT = 1_000_000;

    public const BUSINESS_TIMEZONE = 'Asia/Jakarta';

    public function requestBooking(Account $account, string $withdrawalDate, int $amount, ?string $notes = null): WithdrawalBooking
    {
        $date = CarbonImmutable::parse($withdrawalDate, self::BUSINESS_TIMEZONE)->startOfDay();
        $tomorrow = CarbonImmutable::now(self::BUSINESS_TIMEZONE)->startOfDay()->addDay();

        if ($date->lessThan($tomorrow)) {
            throw new TransactionValidationException('Booking penarikan harus diajukan minimal satu hari sebelumnya.');
        }

        if ($amount <= self::DAILY_LIMIT) {
            throw new TransactionValidationException('Booking hanya diperlukan jika total penarikan sehari melebihi Rp 1.000.000.');
        }

        return DB::transaction(function () use ($account, $date, $amount, $notes) {
            $locked = Account::query()->lockForUpdate()->findOrFail($account->id);

            if ($locked->status !== 'active' || $locked->saver?->status !== 'active') {
                throw new TransactionValidationException('Rekening tidak aktif dan tidak dapat mengajukan booking penarikan.');
            }

            $currentBalance = (int) $locked->balance;
            if ($amount > $currentBalance) {
                throw new TransactionValidationException('Saldo tidak mencukupi untuk mengajukan booking penarikan ini.');
            }

            $minimumBalance = $this->minimumBalanceFor($locked);
            if (($currentBalance - $amount) < $minimumBalance) {
                throw new TransactionValidationException(
                    'Saldo setelah penarikan tidak boleh kurang dari saldo mengendap (Rp '.number_format($minimumBalance, 0, ',', '.').').'
                );
            }

            $existing = WithdrawalBooking::query()
                ->where('account_id', $locked->id)
                ->whereDate('withdrawal_date', $date->toDateString())
                ->whereIn('status', [WithdrawalBooking::STATUS_PENDING, WithdrawalBooking::STATUS_APPROVED])
                ->exists();

            if ($existing) {
                throw new TransactionValidationException('Sudah ada booking yang menunggu persetujuan atau disetujui untuk tanggal tersebut.');
            }

            return WithdrawalBooking::query()->create([
                'account_id' => $locked->id,
                'withdrawal_date' => $date->toDateString(),
                'amount' => $amount,
                'status' => WithdrawalBooking::STATUS_PENDING,
                'notes' => $notes,
            ]);
        });
    }

    public function approve(WithdrawalBooking $booking, User $teller): WithdrawalBooking
    {
        $this->assertTeller($teller);

        return DB::transaction(function () use ($booking, $teller) {
            $locked = WithdrawalBooking::query()->lockForUpdate()->findOrFail($booking->id);
            $account = Account::query()->lockForUpdate()->findOrFail($locked->account_id);

            if ($locked->status !== WithdrawalBooking::STATUS_PENDING) {
                throw new TransactionValidationException('Booking ini sudah diputuskan.');
            }

            $today = CarbonImmutable::now(self::BUSINESS_TIMEZONE)->startOfDay();
            if ($locked->withdrawal_date->toDateString() <= $today->toDateString()) {
                throw new TransactionValidationException('Booking tidak dapat disetujui karena tanggal penarikan sudah tiba atau terlewat.');
            }

            $anotherApproved = WithdrawalBooking::query()
                ->where('account_id', $locked->account_id)
                ->whereDate('withdrawal_date', $locked->withdrawal_date->toDateString())
                ->where('status', WithdrawalBooking::STATUS_APPROVED)
                ->where('id', '<>', $locked->id)
                ->exists();

            if ($anotherApproved) {
                throw new TransactionValidationException('Booking lain untuk tanggal tersebut sudah disetujui.');
            }

            $accountBalance = (int) $account->balance;
            $minimumBalance = $this->minimumBalanceFor($account);
            if ($accountBalance < (int) $locked->amount) {
                throw new TransactionValidationException('Saldo rekening nasabah saat ini tidak mencukupi untuk nominal booking ini.');
            }

            if (($accountBalance - (int) $locked->amount) < $minimumBalance) {
                throw new TransactionValidationException(
                    'Saldo rekening setelah penarikan akan kurang dari saldo mengendap (Rp '.number_format($minimumBalance, 0, ',', '.').').'
                );
            }

            $locked->update([
                'status' => WithdrawalBooking::STATUS_APPROVED,
                'decided_by' => $teller->id,
                'decided_at' => now(),
                'decision_note' => null,
            ]);

            AuditLogService::record('withdrawal_booking_approved', 'withdrawal_booking', $locked->id, [
                'account_id' => $locked->account_id,
                'withdrawal_date' => $locked->withdrawal_date->toDateString(),
                'amount' => $locked->amount,
            ], $teller->id);

            return $locked->refresh();
        });
    }

    public function reject(WithdrawalBooking $booking, User $teller, ?string $reason = null): WithdrawalBooking
    {
        $this->assertTeller($teller);

        return DB::transaction(function () use ($booking, $teller, $reason) {
            $locked = WithdrawalBooking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($locked->status !== WithdrawalBooking::STATUS_PENDING) {
                throw new TransactionValidationException('Booking ini sudah diputuskan.');
            }

            $locked->update([
                'status' => WithdrawalBooking::STATUS_REJECTED,
                'decided_by' => $teller->id,
                'decided_at' => now(),
                'decision_note' => $reason,
            ]);

            AuditLogService::record('withdrawal_booking_rejected', 'withdrawal_booking', $locked->id, [
                'account_id' => $locked->account_id,
                'withdrawal_date' => $locked->withdrawal_date->toDateString(),
                'amount' => $locked->amount,
                'reason' => $reason,
            ], $teller->id);

            return $locked->refresh();
        });
    }

    /** @return array{withdrawn: int, limit: int, remaining: int, booking: WithdrawalBooking|null} */
    public function dailyStatus(Account $account): array
    {
        $today = CarbonImmutable::now(self::BUSINESS_TIMEZONE)->startOfDay();
        $withdrawn = $this->withdrawnBetween($account, $today);
        $booking = WithdrawalBooking::query()
            ->where('account_id', $account->id)
            ->whereDate('withdrawal_date', $today->toDateString())
            ->where('status', WithdrawalBooking::STATUS_APPROVED)
            ->first();
        $limit = max(self::DAILY_LIMIT, $booking ? (int) $booking->amount : 0);

        return [
            'withdrawn' => $withdrawn,
            'limit' => $limit,
            'remaining' => max(0, $limit - $withdrawn),
            'booking' => $booking,
        ];
    }

    public function minimumBalanceFor(Account $account): int
    {
        return $account->effectiveMinimumBalance();
    }

    public function authorizeWithdrawal(Account $account, int $amount, ?int $bookingId): ?WithdrawalBooking
    {
        $today = CarbonImmutable::now(self::BUSINESS_TIMEZONE)->startOfDay();
        $withdrawn = $this->withdrawnBetween($account, $today);
        $totalAfter = $withdrawn + $amount;
        $booking = null;

        if ($bookingId !== null) {
            $booking = WithdrawalBooking::query()->lockForUpdate()->find($bookingId);

            if (
                $booking === null
                || (int) $booking->account_id !== $account->id
                || $booking->withdrawal_date->toDateString() !== $today->toDateString()
                || $booking->status !== WithdrawalBooking::STATUS_APPROVED
            ) {
                throw new TransactionValidationException('Booking penarikan tidak berlaku untuk rekening atau tanggal ini.');
            }
        }

        if ($totalAfter > self::DAILY_LIMIT) {
            if ($booking === null) {
                throw new TransactionValidationException('Total penarikan nasabah hari ini melebihi Rp 1.000.000. Gunakan booking yang telah disetujui teller.');
            }

            if ($totalAfter > $booking->amount) {
                throw new TransactionValidationException('Total penarikan hari ini melebihi nominal booking yang disetujui teller.');
            }
        }

        return $booking;
    }

    private function withdrawnBetween(Account $account, CarbonImmutable $localDay): int
    {
        $start = $localDay->startOfDay()->utc();
        $end = $localDay->addDay()->startOfDay()->utc();

        return (int) Transaction::query()
            ->where('account_id', $account->id)
            ->where('type', 'withdrawal')
            ->where('status', 'success')
            ->whereNull('reversed_at')
            ->where('transaction_at', '>=', $start)
            ->where('transaction_at', '<', $end)
            ->sum('amount');
    }

    private function assertTeller(User $user): void
    {
        if ($user->role !== 'teller') {
            abort(403);
        }
    }
}
