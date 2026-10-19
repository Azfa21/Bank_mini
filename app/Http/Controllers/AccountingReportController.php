<?php

namespace App\Http\Controllers;

use App\Models\JurnalAkuntansi;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingReportController extends Controller
{
    public function __construct(
        protected AccountingService $accounting,
    ) {}

    /**
     * Halaman gabungan: ringkasan saldo akun kas di atas, rincian mutasi akun
     * terpilih di bawah. Keduanya berbagi satu rentang tanggal supaya angka saldo
     * dan mutasi bisa dirujuk silang.
     */
    public function generalLedger(Request $request)
    {
        $this->authorize('viewAny', JurnalAkuntansi::class);

        // Alasan: daftar akun tampil dipakai untuk memvalidasi URL, bukan hanya untuk
        // mengisi dropdown. Tanpa ini akun tersembunyi masih bisa dibuka lewat bookmark
        // lama `?account_code=201`.
        $accounts = AccountingService::presentedAccounts();
        $requestedAccountCode = (string) $request->input('account_code', AccountingService::ACCOUNT_CODE_CASH_TELLER);
        $accountCode = AccountingService::isPresented($requestedAccountCode)
            ? $requestedAccountCode
            : AccountingService::ACCOUNT_CODE_CASH_TELLER;

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = JurnalAkuntansi::query()
            ->when($accountCode, fn ($q) => $q->where('account_code', $accountCode))
            ->when($dateFrom, fn ($q) => $q->whereDate('journal_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('journal_at', '<=', $dateTo))
            ->orderBy('journal_at')
            ->orderBy('id');

        $entries = $query->paginate(25)->withQueryString()->fragment('mutasi');

        $totalDebit = (int) (clone $query)->sum('debit_amount');
        $totalCredit = (int) (clone $query)->sum('credit_amount');

        // Saldo kumulatif sampai batas atas rentang; tanpa filter berarti sampai hari ini.
        $balanceDate = $dateTo ?: now()->toDateString();
        $trialBalance = $this->accounting->trialBalance($balanceDate);

        // Alasan: hanya akun tampil yang dijumlahkan ke total halaman ini. Memakai grand
        // total milik seluruh akun akan membuat baris tabel tidak cocok dengan footernya.
        $balances = array_intersect_key($trialBalance['balances'], $accounts);
        $grandDebit = 0;
        $grandCredit = 0;

        foreach ($balances as $balance) {
            $grandDebit += $balance['net_debit'];
            $grandCredit += $balance['net_credit'];
        }

        // Alasan: indikator seimbang tetap dihitung dari seluruh akun, termasuk akun yang
        // tidak ditampilkan, supaya statusnya tidak pernah berbohong.
        $isBalanced = $trialBalance['is_balanced'];

        $normalBalance = $accounts[$accountCode]['normal'] ?? 'debit';
        $toNormalSide = fn (int $debitSign): int => $normalBalance === 'debit' ? $debitSign : -$debitSign;

        // Saldo awal hanya berarti saat ada batas bawah; tanpa itu seluruh riwayat tampil.
        $openingBalance = $dateFrom
            ? $toNormalSide($this->cumulativeBalance($accountCode, Carbon::parse($dateFrom)->subDay()->toDateString()))
            : 0;

        // Saldo akhir mengikuti neraca saldo (kumulatif), bukan hanya mutasi rentang,
        // supaya baris akun di tabel atas dan angka di bawah selalu sama.
        $endingBalance = $toNormalSide($this->cumulativeBalance($accountCode, $balanceDate));

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'account_code' => $accountCode,
                    'account_name' => $this->accounting->accountName($accountCode),
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'opening_balance' => $openingBalance,
                    'ending_balance' => $endingBalance,
                    'entries' => $entries,
                    'trial_balance' => [
                        'date' => $trialBalance['date'],
                        'balances' => $balances,
                        'grand_debit' => $grandDebit,
                        'grand_credit' => $grandCredit,
                        'is_balanced' => $isBalanced,
                    ],
                ],
            ]);
        }

        return view('reports.general_ledger', compact(
            'entries',
            'accountCode',
            'accounts',
            'normalBalance',
            'openingBalance',
            'totalDebit',
            'totalCredit',
            'endingBalance',
            'dateFrom',
            'dateTo',
            'balances',
            'grandDebit',
            'grandCredit',
            'isBalanced',
            'balanceDate'
        ));
    }

    /**
     * Neraca saldo tetap tersedia sebagai JSON untuk konsumen API; halaman web-nya
     * sudah menyatu ke buku besar lewat redirect di routes/web.php.
     */
    public function trialBalance(Request $request)
    {
        $this->authorize('viewAny', JurnalAkuntansi::class);

        $trialBalance = $this->accounting->trialBalance(
            $request->input('date', now()->toDateString())
        );

        $balances = $trialBalance['balances'];

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $trialBalance['date'],
                'balances' => $balances,
                'grand_debit' => $trialBalance['grand_debit'],
                'grand_credit' => $trialBalance['grand_credit'],
                'is_balanced' => $trialBalance['is_balanced'],
                'liquidity' => $this->liquiditySummary(
                    $balances[AccountingService::ACCOUNT_CODE_CASH_TELLER]['net_debit'],
                    $balances[AccountingService::ACCOUNT_CODE_CASH_VAULT]['net_debit'],
                    $balances[AccountingService::ACCOUNT_CODE_SAVINGS]['net_credit'],
                ),
            ],
        ]);
    }

    /**
     * Saldo kumulatif satu akun sampai tanggal tertentu, dalam orientasi debit
     * (debit - kredit). Dipakai halaman buku besar untuk saldo awal dan akhir.
     */
    private function cumulativeBalance(string $accountCode, ?string $date): int
    {
        $totals = JurnalAkuntansi::query()
            ->where('account_code', $accountCode)
            ->when($date, fn ($q) => $q->whereDate('journal_at', '<=', $date))
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
            ->first();

        return (int) ($totals->total_debit ?? 0) - (int) ($totals->total_credit ?? 0);
    }

    /**
     * Kas fisik di laci dan brankas dibanding kewajiban simpanan nasabah.
     * Alasan: dipakai halaman gabungan dan endpoint JSON neraca saldo agar
     * rasio likuiditas tidak pernah berbeda antar keduanya.
     *
     * @return array{cash_in_teller: int, cash_in_vault: int, total_physical_cash: int, total_savings: int, liquidity_ratio_percent: float}
     */
    private function liquiditySummary(int $cashInTeller, int $cashInVault, int $totalSavingsObligation): array
    {
        $totalPhysicalCash = $cashInTeller + $cashInVault;

        return [
            'cash_in_teller' => $cashInTeller,
            'cash_in_vault' => $cashInVault,
            'total_physical_cash' => $totalPhysicalCash,
            'total_savings' => $totalSavingsObligation,
            'liquidity_ratio_percent' => $totalSavingsObligation > 0
                ? round(($totalPhysicalCash / $totalSavingsObligation) * 100, 2)
                : 100.0,
        ];
    }

    public function exportCsv(Request $request, string $type = 'journals'): StreamedResponse
    {
        $this->authorize('viewAny', JurnalAkuntansi::class);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$type.'-'.now()->format('Ymd_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ];

        return response()->stream(function () use ($type, $request) {
            $accounting = app(AccountingService::class);
            $presentedCodes = array_keys(AccountingService::presentedAccounts());
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            if ($type === 'general_ledger') {
                fputcsv($handle, ['No. Jurnal', 'Tanggal', 'Kode Akun', 'Nama Akun', 'Debit (Rp)', 'Kredit (Rp)', 'Keterangan', 'Status']);

                // Alasan: ekspor mengikuti filter halaman. Kode akun yang tidak tampil
                // dikembalikan ke akun kas supaya berkasnya tidak pernah memuat akun itu.
                $requestedCode = (string) $request->input('account_code');
                $code = AccountingService::isPresented($requestedCode)
                    ? $requestedCode
                    : AccountingService::ACCOUNT_CODE_CASH_TELLER;
                $dateFrom = $request->input('date_from');
                $dateTo = $request->input('date_to');

                $query = JurnalAkuntansi::query()
                    ->where('account_code', $code)
                    ->when($dateFrom, fn ($q) => $q->whereDate('journal_at', '>=', $dateFrom))
                    ->when($dateTo, fn ($q) => $q->whereDate('journal_at', '<=', $dateTo))
                    ->orderBy('journal_at')
                    ->orderBy('id');

                $query->chunk(500, function ($rows) use ($handle, $accounting) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->journal_number,
                            $row->journal_at->format('Y-m-d H:i:s'),
                            $row->account_code,
                            $accounting->accountName($row->account_code),
                            $row->debit_amount,
                            $row->credit_amount,
                            $row->description,
                            $row->status,
                        ]);
                    }
                });
            } elseif ($type === 'trial_balance') {
                $date = $request->input('date_to') ?: $request->input('date') ?: now()->toDateString();
                $trialBalance = $accounting->trialBalance($date);
                $balances = array_intersect_key($trialBalance['balances'], AccountingService::presentedAccounts());

                fputcsv($handle, ['Per Tanggal', $trialBalance['date']]);
                fputcsv($handle, ['Kode Akun', 'Nama Akun', 'Total Debit (Rp)', 'Total Kredit (Rp)', 'Saldo Debit (Rp)', 'Saldo Kredit (Rp)']);

                $totalDebit = 0;
                $totalCredit = 0;

                foreach ($balances as $code => $balance) {
                    $totalDebit += $balance['net_debit'];
                    $totalCredit += $balance['net_credit'];

                    fputcsv($handle, [
                        $code,
                        $balance['name'],
                        $balance['total_debit'],
                        $balance['total_credit'],
                        $balance['net_debit'],
                        $balance['net_credit'],
                    ]);
                }

                fputcsv($handle, ['Total kas', '', '', '', $totalDebit, $totalCredit]);
                // Alasan: status ini dihitung dari seluruh akun (termasuk akun yang tidak
                // ditampilkan), jadi labelnya menyebut lingkupnya agar tidak menyesatkan.
                fputcsv($handle, ['Status keseimbangan seluruh akun', $trialBalance['is_balanced'] ? 'Seimbang' : 'Tidak seimbang']);
            } else {
                fputcsv($handle, ['No. Jurnal', 'No. Transaksi', 'Tanggal', 'Kode Akun', 'Jenis', 'Debit (Rp)', 'Kredit (Rp)', 'Keterangan']);

                JurnalAkuntansi::with('transaction')
                    ->whereIn('account_code', $presentedCodes)
                    ->orderBy('id')
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $row) {
                            $relatedNumber = $row->transaction !== null
                                ? $row->transaction->receipt_number
                                : '-';

                            fputcsv($handle, [
                                $row->journal_number,
                                $relatedNumber,
                                $row->journal_at->format('Y-m-d H:i:s'),
                                $row->account_code,
                                $row->entry_type,
                                $row->debit_amount,
                                $row->credit_amount,
                                $row->description,
                            ]);
                        }
                    });
            }

            fclose($handle);
        }, 200, $headers);
    }
}
