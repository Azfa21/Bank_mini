<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AccountingService
{
    public const ACCOUNT_CODE_CASH_TELLER = '101';

    public const ACCOUNT_CODE_CASH_VAULT = '102';

    public const ACCOUNT_CODE_SAVINGS = '201';

    /**
     * Alasan: `presented` memisahkan akun yang boleh tampil di layar staf dari akun yang
     * hanya dipakai mesin jurnal. Akun 201 tetap diposting supaya buku seimbang, tetapi
     * sekolah memutuskan akun itu tidak ditampilkan ke pengguna mana pun.
     *
     * @var array{
     *     101: array{name: string, normal: string, type: string, presented: bool},
     *     102: array{name: string, normal: string, type: string, presented: bool},
     *     201: array{name: string, normal: string, type: string, presented: bool}
     * }
     */
    public const CHART_OF_ACCOUNTS = [
        self::ACCOUNT_CODE_CASH_TELLER => ['name' => 'Kas Teller (Laci Loket)', 'normal' => 'debit', 'type' => 'asset', 'presented' => true],
        self::ACCOUNT_CODE_CASH_VAULT => ['name' => 'Kas Brankas Utama (Khazanah)', 'normal' => 'debit', 'type' => 'asset', 'presented' => true],
        self::ACCOUNT_CODE_SAVINGS => ['name' => 'Tabungan Nasabah (Simpanan Siswa)', 'normal' => 'credit', 'type' => 'liability', 'presented' => false],
    ];

    /**
     * Akun yang boleh muncul di layar dan berkas ekspor staf.
     *
     * @return array{
     *     101: array{name: string, normal: string, type: string, presented: bool},
     *     102: array{name: string, normal: string, type: string, presented: bool}
     * }
     */
    public static function presentedAccounts(): array
    {
        return array_filter(
            self::CHART_OF_ACCOUNTS,
            static fn (array $info): bool => $info['presented'],
        );
    }

    /**
     * Menentukan apakah kode akun boleh ditampilkan. Dipakai controller untuk menolak
     * `?account_code=201` dari URL atau bookmark lama, bukan hanya menyembunyikannya
     * dari daftar pilihan.
     */
    public static function isPresented(string $accountCode): bool
    {
        return (self::CHART_OF_ACCOUNTS[$accountCode]['presented'] ?? false) === true;
    }

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
