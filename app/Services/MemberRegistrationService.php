<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Facades\DB;

class MemberRegistrationService
{
    public function __construct(
        protected SequenceService $sequences,
    ) {}

    /**
     * @param  array{nis?: string|null, full_name: string, graduation_year?: string|null}  $data
     */
    public function register(array $data, ?int $createdBy = null): Member
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $nis = isset($data['nis']) ? trim((string) $data['nis']) : '';

            if ($nis === '') {
                $year = now()->format('Y');

                do {
                    $candidate = sprintf('%s%04d', $year, $this->sequences->next("NIS-{$year}"));
                } while (Member::where('nis', $candidate)->exists());

                $nis = $candidate;
            }

            $member = Member::create([
                'nis' => $nis,
                'full_name' => $data['full_name'],
                'graduation_year' => $data['graduation_year'] ?? null,
                'status' => Member::STATUS_ACCOUNT_INACTIVE,
                'created_by' => $createdBy,
            ]);

            AuditLogService::record('member_registered', 'member', null, [
                'nis' => $member->nis,
                'full_name' => $member->full_name,
                'graduation_year' => $member->graduation_year,
            ], $createdBy);

            return $member;
        });
    }
}
