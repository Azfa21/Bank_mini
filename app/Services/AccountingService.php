<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AccountingService
{
    public const ACCOUNT_CODE_CASH_TELLER = '101';

    public const ACCOUNT_CODE_CASH_VAULT = '102';

    public const ACCOUNT_CODE_SAVINGS = '201';

    /**
     * @var array{
     *     101: array{name: string, normal: string, type: string},
     *     102: array{name: string, normal: string, type: string},
     *     201: array{name: string, normal: string, type: string}
     * }
     */
    public const CHART_OF_ACCOUNTS = [
        self::ACCOUNT_CODE_CASH_TELLER => ['name' => 'Kas Teller (Laci Loket)', 'normal' => 'debit', 'type' => 'asset'],
        self::ACCOUNT_CODE_CASH_VAULT => ['name' => 'Kas Brankas Utama (Khazanah)', 'normal' => 'debit', 'type' => 'asset'],
        self::ACCOUNT_CODE_SAVINGS => ['name' => 'Tabungan Nasabah (Simpanan Siswa)', 'normal' => 'credit', 'type' => 'liability'],
    ];

    public function accountName(string $accountCode): string
    {
        return self::CHART_OF_ACCOUNTS[$accountCode]['name'] ?? 'Akun '.$accountCode;
    }

    /**
     * Neraca saldo kumulatif sampai tanggal tertentu, dihitung dari satu
     * query agregasi jurnal.
     *
     * @return array{
     *     date: string,
     *     balances: array{
     *         101: array{name: string, total_debit: int, total_credit: int, net_debit: int, net_credit: int},
     *         102: array{name: string, total_debit: int, total_credit: int, net_debit: int, net_credit: int},
     *         201: array{name: string, total_debit: int, total_credit: int, net_debit: int, net_credit: int}
     *     },
     *     grand_debit: int,
     *     grand_credit: int,
     *     is_balanced: bool
     * }
     */
    public function trialBalance(?string $date = null): array
    {
        $date ??= now()->toDateString();

        $totals = DB::table('jurnal_akuntansi')
            ->whereDate('journal_at', '<=', $date)
            ->selectRaw('account_code, SUM(debit_amount) as total_debit, SUM(credit_amount) as total_credit')
            ->groupBy('account_code')
            ->get()
            ->keyBy('account_code');

        $balances = [];
        $grandDebit = 0;
        $grandCredit = 0;

        foreach (self::CHART_OF_ACCOUNTS as $code => $info) {
            $totalDebit = (int) ($totals[$code]->total_debit ?? 0);
            $totalCredit = (int) ($totals[$code]->total_credit ?? 0);

            if ($info['normal'] === 'debit') {
                $netDebit = max(0, $totalDebit - $totalCredit);
                $netCredit = max(0, $totalCredit - $totalDebit);
            } else {
                $netCredit = max(0, $totalCredit - $totalDebit);
                $netDebit = max(0, $totalDebit - $totalCredit);
            }

            $balances[$code] = [
                'name' => $info['name'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'net_debit' => $netDebit,
                'net_credit' => $netCredit,
            ];

            $grandDebit += $netDebit;
            $grandCredit += $netCredit;
        }

        return [
            'date' => $date,
            'balances' => $balances,
            'grand_debit' => $grandDebit,
            'grand_credit' => $grandCredit,
            'is_balanced' => $grandDebit === $grandCredit,
        ];
    }
}
