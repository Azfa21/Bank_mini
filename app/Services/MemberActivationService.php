<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\Member;
use App\Models\Saver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberActivationService
{
    public function __construct(
        protected AccountService $accounts,
        protected TransactionService $transactions,
    ) {}

    /**
     * Promote an inactive member into a saver by opening an account and posting
     * the qualifying first deposit, all inside one database transaction.
     *
     * @param  array{amount: int, slip_number?: string|null, pin: string, email?: string|null, class_name?: string|null, phone_number?: string|null}  $data
     * @return array{saver: Saver, account: Account, transaction: Transaction}
     */
    public function activate(Member $member, array $data, User $teller): array
    {
        return DB::transaction(function () use ($member, $data, $teller) {
            $locked = Member::query()->lockForUpdate()->findOrFail($member->nis);

            if ($locked->status !== Member::STATUS_ACCOUNT_INACTIVE) {
                throw new TransactionValidationException('Anggota ini sudah memiliki rekening aktif.');
            }

            if (Saver::withTrashed()->where('member_nis', $locked->nis)->exists()) {
                throw new TransactionValidationException('Anggota ini sudah terdaftar sebagai penabung.');
            }

            $saver = Saver::create([
                'student_number' => $locked->nis,
                'member_nis' => $locked->nis,
                'full_name' => $locked->full_name,
                'class_name' => $data['class_name'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'email' => $data['email'] ?? null,
                'pin_hash' => $data['pin'],
                'qr_code_value' => Str::uuid()->toString(),
                'created_by' => $teller->id,
                'status' => 'active',
            ]);

            $account = $this->accounts->openFor($saver);

            $transaction = $this->transactions->deposit($account, [
                'amount' => (int) $data['amount'],
                'slip_number' => $data['slip_number'] ?? null,
            ], $teller);

            $locked->update(['status' => Member::STATUS_ACCOUNT_ACTIVE]);

            AuditLogService::record('member_activated', 'saver', $saver->id, [
                'nis' => $locked->nis,
                'account_number' => $account->account_number,
                'amount' => (int) $data['amount'],
            ], $teller->id);

            return [
                'saver' => $saver,
                'account' => $account,
                'transaction' => $transaction,
            ];
        });
    }
}
