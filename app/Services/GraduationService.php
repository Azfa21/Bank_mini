<?php

namespace App\Services;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\Member;
use App\Models\MobileToken;
use App\Models\Saver;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GraduationService
{
    public function __construct(
        protected TransactionService $transactions,
        protected AccountClosureService $closures,
    ) {}

    /**
     * Daftar anggota satu angkatan, lengkap dengan relasi rekeningnya.
     *
     * @return Collection<int, Member>
     */
    public function membersFor(string $year): Collection
    {
        return Member::query()
            ->with(['saver.account.pendingClosure.requester'])
            ->where('graduation_year', $year)
            ->orderBy('full_name')
            ->get();
    }

    public function stateFor(Member $member): string
    {
        if ($member->isGraduated()) {
            return 'alumni';
        }

        $saver = $member->saver;

        if ($saver === null) {
            return 'roster_only';
        }

        if ($saver->isGraduated()) {
            return 'alumni';
        }

        $account = $saver->account;

        if ($account === null || $account->status !== 'active') {
            return 'ready';
        }

        if ($account->hasPendingClosure()) {
            return 'pending_approval';
        }

        // Sisa saldo tinggal saldo mengendap berarti tidak ada lagi yang bisa
        // dicairkan di loket, jadi rekening siap diarsipkan otomatis.
        return (int) $account->balance <= $this->transactions->minimumBalanceFor($account)
            ? 'ready'
            : 'pending_counter';
    }

    /**
     * Mengarsipkan satu angkatan. Hanya anggota tanpa rekening dan penabung yang
     * sisa saldonya tinggal saldo mengendap yang diproses; penabung yang masih
     * punya saldo di atas saldo mengendap dibiarkan untuk diselesaikan di loket.
     *
     * @return array{members: int, archived: int, already_graduated: int, pending_counter: int}
     */
    public function graduateBatch(string $year, ?User $admin = null): array
    {
        return DB::transaction(function () use ($year, $admin) {
            $members = $this->membersFor($year);

            $archived = 0;
            $alreadyGraduated = 0;
            $pendingCounter = 0;

            foreach ($members as $member) {
                $state = $this->stateFor($member);

                if ($state === 'alumni') {
                    $alreadyGraduated++;

                    continue;
                }

                if ($state === 'pending_counter') {
                    $pendingCounter++;

                    continue;
                }

                if ($state === 'pending_approval') {
                    // Penutupan sudah diajukan Teller, cukup tandai anggota saat disetujui Administrator.
                    continue;
                }

                $saver = $member->saver;
                $account = $saver?->account;

                if ($saver !== null && $account !== null && $account->status === 'active') {
                    $this->closeWithoutPayout($account, $saver);
                } elseif ($saver !== null && ! $saver->isGraduated()) {
                    $saver->update(['status' => Saver::STATUS_GRADUATED, 'graduated_at' => now()]);
                }

                $member->update(['graduated_at' => now()]);
                $archived++;
            }

            AuditLogService::record('graduation_batch_processed', 'member', null, [
                'graduation_year' => $year,
                'archived' => $archived,
                'pending_counter' => $pendingCounter,
            ], $admin?->id);

            return [
                'members' => $members->count(),
                'archived' => $archived,
                'already_graduated' => $alreadyGraduated,
                'pending_counter' => $pendingCounter,
            ];
        });
    }

    public function graduateMember(Member $member, ?User $admin = null): bool
    {
        return DB::transaction(function () use ($member, $admin) {
            if ($member->isGraduated()) {
                return false;
            }

            $saver = Saver::withTrashed()->where('member_nis', $member->nis)->first();

            if ($saver !== null && ! $saver->isGraduated()) {
                $account = $saver->account;

                if ($account !== null && $account->status === 'active' && (int) $account->balance > $this->transactions->minimumBalanceFor($account)) {
                    return false;
                }

                if ($account !== null && $account->status === 'active') {
                    $this->closeWithoutPayout($account, $saver);
                } else {
                    $saver->update(['status' => Saver::STATUS_GRADUATED, 'graduated_at' => now()]);
                }
            }

            $member->update(['graduated_at' => now()]);

            AuditLogService::record('member_graduated', 'member', null, [
                'nis' => $member->nis,
                'full_name' => $member->full_name,
            ], $admin?->id);

            return true;
        });
    }

    /**
     * Menandai anggota tidak lulus. Pencairan yang sudah dilakukan Teller dibatalkan
     * supaya saldo kembali utuh, lalu anggota digeser ke angkatan berikutnya agar
     * tidak ikut diproses pada angkatan ini.
     *
     * @return array{member: Member, previous_year: string|null, next_year: string|null, reversed: bool}
     */
    public function notGraduate(Member $member, User $admin, string $expectedYear, ?string $reason = null): array
    {
        return DB::transaction(function () use ($member, $admin, $expectedYear, $reason) {
            $locked = Member::query()->lockForUpdate()->findOrFail($member->nis);

            if ($locked->isGraduated()) {
                throw new TransactionValidationException('Anggota ini sudah diarsipkan sebagai alumni.');
            }

            if ((string) $locked->graduation_year !== $expectedYear) {
                throw new TransactionValidationException('Data angkatan sudah berubah. Muat ulang halaman sebelum menandai tidak lulus.');
            }

            $saver = Saver::query()->withTrashed()->where('member_nis', $locked->nis)->lockForUpdate()->first();

            if ($saver?->isGraduated()) {
                throw new TransactionValidationException('Penabung ini sudah tercatat sebagai alumni.');
            }

            $reversed = false;

            if ($saver !== null) {
                $account = Account::query()->where('saver_id', $saver->id)->lockForUpdate()->first();
                $closure = $account?->pendingClosure()->lockForUpdate()->first();

                if ($closure !== null) {
                    $this->closures->reject(
                        $closure,
                        $admin,
                        $reason ?: 'Tidak lulus angkatan '.($locked->graduation_year ?? '-'),
                    );
                    $reversed = true;
                }
            }

            $previousYear = $locked->graduation_year;
            $nextYear = $this->nextGraduationYear($previousYear);

            $locked->update(['graduation_year' => $nextYear]);

            AuditLogService::record('member_not_graduated', 'member', null, [
                'nis' => $locked->nis,
                'full_name' => $locked->full_name,
                'previous_graduation_year' => $previousYear,
                'next_graduation_year' => $nextYear,
                'payout_reversed' => $reversed,
            ], $admin->id);

            return [
                'member' => $locked,
                'previous_year' => $previousYear,
                'next_year' => $nextYear,
                'reversed' => $reversed,
            ];
        });
    }

    protected function nextGraduationYear(?string $year): ?string
    {
        $year = trim((string) $year);

        return preg_match('/^\d{4}$/', $year) === 1
            ? (string) ((int) $year + 1)
            : null;
    }

    protected function closeWithoutPayout(Account $account, Saver $saver): void
    {
        $account->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $saver->update([
            'status' => Saver::STATUS_GRADUATED,
            'graduated_at' => now(),
        ]);

        MobileToken::query()->where('saver_id', $saver->id)->delete();
    }
}
