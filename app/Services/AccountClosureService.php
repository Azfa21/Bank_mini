<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\AccountClosure;
use App\Models\Member;
use App\Models\MobileToken;
use App\Models\Saver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountClosureService
{
    public function __construct(
        protected TransactionService $transactions,
        protected ReversalService $reversals,
    ) {}

    /**
     * Teller mencairkan saldo di atas saldo mengendap di loket, sisa saldo
     * mengendap tetap tersimpan. Persetujuan Administrator yang menjadikan
     * penabung sebagai alumni.
     */
    public function request(Account $account, User $teller, string $pin, ?string $notes): AccountClosure
    {
        $saver = $account->saver()->first();

        if ($saver === null) {
            throw new TransactionValidationException('Data nasabah tidak ditemukan.');
        }

        $this->transactions->verifySaverPin($saver, $pin, 'penutupan rekening', $teller->id);

        return DB::transaction(function () use ($account, $saver, $teller, $notes) {
            $locked = Account::query()->lockForUpdate()->findOrFail($account->id);

            if ($locked->status !== 'active') {
                throw new TransactionValidationException('Rekening sudah tidak aktif.');
            }

            if ($locked->hasPendingClosure()) {
                throw new TransactionValidationException('Sudah ada permintaan penutupan yang menunggu persetujuan.');
            }

            $payout = $this->transactions->withdrawClosurePayout(
                $locked,
                $teller,
                'Pencairan saldo penutupan rekening karena kelulusan',
            );

            $payoutAmount = $payout->amount ?? 0;

            $closure = AccountClosure::create([
                'account_id' => $locked->id,
                'saver_id' => $saver->id,
                'requested_by' => $teller->id,
                'payout_transaction_id' => $payout?->id,
                'payout_amount' => $payoutAmount,
                'status' => AccountClosure::STATUS_PENDING,
                'notes' => $notes,
            ]);

            AuditLogService::record('account_closure_requested', 'account_closure', $closure->id, [
                'account_number' => $locked->account_number,
                'saver_name' => $saver->full_name,
                'payout_amount' => $payoutAmount,
            ], $teller->id);

            return $closure;
        });
    }

    public function approve(AccountClosure $closure, User $admin, ?string $notes): AccountClosure
    {
        return DB::transaction(function () use ($closure, $admin, $notes) {
            $locked = AccountClosure::query()->lockForUpdate()->findOrFail($closure->id);

            if (! $locked->isPending()) {
                throw new TransactionValidationException('Permintaan penutupan ini sudah diputuskan sebelumnya.');
            }

            $account = Account::query()->lockForUpdate()->findOrFail($locked->account_id);

            if ($account->status !== 'active') {
                throw new TransactionValidationException('Rekening sudah tidak aktif.');
            }

            // Lapisan pengaman: sisa saldo tidak boleh masih di atas saldo
            // mengendap saat permintaan penutupan disetujui.
            if ((int) $account->balance > $this->transactions->minimumBalanceFor($account)) {
                throw new TransactionValidationException('Saldo rekening masih di atas saldo mengendap. Minta Teller mencairkan sisa saldo terlebih dahulu.');
            }

            $saver = Saver::query()->withTrashed()->lockForUpdate()->findOrFail($locked->saver_id);

            $account->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            $saver->update([
                'status' => Saver::STATUS_GRADUATED,
                'graduated_at' => now(),
            ]);

            if ($saver->member_nis !== null) {
                Member::query()->whereKey($saver->member_nis)->update(['graduated_at' => now()]);
            }

            MobileToken::query()->where('saver_id', $saver->id)->delete();

            $locked->update([
                'status' => AccountClosure::STATUS_APPROVED,
                'decided_by' => $admin->id,
                'decided_at' => now(),
                'decision_notes' => $notes,
            ]);

            AuditLogService::record('account_closure_approved', 'account_closure', $locked->id, [
                'account_number' => $account->account_number,
                'saver_name' => $saver->full_name,
                'payout_amount' => $locked->payout_amount,
            ], $admin->id);

            return $locked;
        });
    }

    /**
     * Menolak permintaan membatalkan pencairan yang sudah dilakukan Teller,
     * sehingga saldo penabung kembali dan rekening tetap dapat dipakai.
     */
    public function reject(AccountClosure $closure, User $admin, ?string $notes): AccountClosure
    {
        return DB::transaction(function () use ($closure, $admin, $notes) {
            $locked = AccountClosure::query()->lockForUpdate()->findOrFail($closure->id);

            if (! $locked->isPending()) {
                throw new TransactionValidationException('Permintaan penutupan ini sudah diputuskan sebelumnya.');
            }

            if ($locked->payout_transaction_id !== null) {
                $payout = Transaction::query()->find($locked->payout_transaction_id);

                if ($payout !== null && $payout->status === 'success') {
                    $this->reversals->reverse(
                        $payout,
                        $admin,
                        'Pembatalan penutupan rekening: '.($notes ?: 'ditolak Administrator'),
                    );
                }
            }

            $locked->update([
                'status' => AccountClosure::STATUS_REJECTED,
                'decided_by' => $admin->id,
                'decided_at' => now(),
                'decision_notes' => $notes,
            ]);

            AuditLogService::record('account_closure_rejected', 'account_closure', $locked->id, [
                'reason' => $notes,
            ], $admin->id);

            return $locked;
        });
    }
}
