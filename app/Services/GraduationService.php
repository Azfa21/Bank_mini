<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Member;
use App\Models\MobileToken;
use App\Models\Saver;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GraduationService
{
    /**
     * Daftar anggota satu angkatan, lengkap dengan relasi rekeningnya.
     *
     * @return Collection<int, Member>
     */
    public function membersFor(string $year): Collection
    {
        return Member::query()
            ->with(['saver.account'])
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

        return (int) $account->balance === 0 ? 'ready' : 'pending_counter';
    }

    /**
     * Mengarsipkan satu angkatan. Hanya anggota tanpa rekening dan penabung bersaldo nol
     * yang diproses; penabung yang masih bersaldo dibiarkan untuk diselesaikan di loket.
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

                if ($account !== null && $account->status === 'active' && (int) $account->balance > 0) {
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
