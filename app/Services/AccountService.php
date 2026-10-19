<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Saver;

class AccountService
{
    public const DEFAULT_MINIMUM_BALANCE = 10000;

    public const ACCOUNT_NUMBER_PREFIX = '10';

    private const ORDINAL_PAD = 4;

    public function __construct(
        protected SequenceService $sequences,
    ) {}

    /**
     * Tahun ajaran berjalan menurut kalender, misalnya "2026/2027". Dipakai saat membuka
     * rekening dan sebagai penanda tahun kenaikan kelas.
     */
    public static function academicYear(): string
    {
        return now()->format('Y').'/'.(now()->format('Y') + 1);
    }

    public function openFor(Saver $saver, int $minimumBalance = self::DEFAULT_MINIMUM_BALANCE): Account
    {
        $academicYear = self::academicYear();

        $startYear = now()->format('y');
        $endYear = (int) $startYear + 1;

        $ordinal = $this->sequences->next("ACC-{$startYear}{$endYear}");

        return Account::create([
            'account_number' => self::ACCOUNT_NUMBER_PREFIX.$startYear.$endYear.str_pad((string) $ordinal, self::ORDINAL_PAD, '0', STR_PAD_LEFT),
            'saver_id' => $saver->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => $minimumBalance,
            'academic_year' => $academicYear,
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }
}
