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

        $accounts = AccountingService::presentedAccounts();
        $requestedAccountCode = trim((string) $request->input('account_code', ''));
        $accountCode = $requestedAccountCode !== '' && AccountingService::isPresented($requestedAccountCode)
            ? $requestedAccountCode
            : '';
        $apiAccountCode = $accountCode ?: AccountingService::ACCOUNT_CODE_CASH_TELLER;

        $dateFrom = $request->input('date_from') ?: null;
        $dateTo = $request->input('date_to') ?: null;
        $search = trim((string) $request->input('search', $request->input('transaction_number', '')));

        $queryAccountCode = $this->wantsJson($request) ? $apiAccountCode : $accountCode;
        $makeQuery = fn () => $this->cashJournalQuery(
            $queryAccountCode,
            $dateFrom,
            $dateTo,
            $search,
        );

        $entries = $makeQuery()
            ->with(['transaction', 'cashTransfer', 'cashRequest'])
            ->orderBy('journal_at')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString()
            ->fragment('mutasi');

        $totalDebit = (int) $makeQuery()->sum('debit_amount');
        $totalCredit = (int) $makeQuery()->sum('credit_amount');

        $balanceDate = $dateTo ?: now()->toDateString();
        $trialBalance = $this->accounting->trialBalance($balanceDate);

        $balances = array_intersect_key($trialBalance['balances'], $accounts);
        $grandDebit = 0;
        $grandCredit = 0;

        foreach ($balances as $balance) {
            $grandDebit += $balance['net_debit'];
            $grandCredit += $balance['net_credit'];
        }

        $isBalanced = $trialBalance['is_balanced'];

        $periodTotals = $makeQuery()
            ->selectRaw('account_code, COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
            ->groupBy('account_code')
            ->get()
            ->keyBy('account_code');

        $visibleBalances = $accountCode === ''
            ? $balances
            : array_intersect_key($balances, [$accountCode => true]);
        $summaryBalances = [];
        $cashEndingBalance = 0;

        foreach ($visibleBalances as $code => $balance) {
            $period = $periodTotals->get($code);
            $ending = $balance['net_debit'] - $balance['net_credit'];

            $summaryBalances[$code] = [
                'name' => $balance['name'],
                'total_debit' => (int) ($period->total_debit ?? 0),
                'total_credit' => (int) ($period->total_credit ?? 0),
                'ending_balance' => $ending,
            ];
            $cashEndingBalance += $ending;
        }

        $normalBalance = $accounts[$apiAccountCode]['normal'];
        $toNormalSide = fn (int $debitSign): int => $normalBalance === 'debit' ? $debitSign : -$debitSign;

        $openingBalance = $dateFrom
            ? $toNormalSide($this->cumulativeBalance($apiAccountCode, Carbon::parse($dateFrom)->subDay()->toDateString()))
            : 0;

        $endingBalance = $toNormalSide($this->cumulativeBalance($apiAccountCode, $balanceDate));

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'account_code' => $apiAccountCode,
                    'account_name' => $this->accounting->accountName($apiAccountCode),
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
            'totalDebit',
            'totalCredit',
            'dateFrom',
            'dateTo',
            'search',
            'summaryBalances',
            'cashEndingBalance',
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
     * Kas fisik di laci dan kas utama dibanding kewajiban simpanan nasabah.
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
                fputcsv($handle, ['No. Jurnal', 'Referensi', 'Tanggal & Waktu', 'Kode Akun', 'Nama Akun', 'Debit (Rp)', 'Kredit (Rp)', 'Keterangan']);

                $requestedCode = trim((string) $request->input('account_code', ''));
                $code = AccountingService::isPresented($requestedCode) ? $requestedCode : '';
                $dateFrom = $request->input('date_from');
                $dateTo = $request->input('date_to');
                $search = trim((string) $request->input('search', $request->input('transaction_number', '')));

                $query = $this->cashJournalQuery($code, $dateFrom ?: null, $dateTo ?: null, $search)
                    ->with(['transaction', 'cashTransfer', 'cashRequest'])
                    ->orderBy('journal_at')
                    ->orderBy('id');

                $query->chunk(500, function ($rows) use ($handle, $accounting) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->journal_number,
                            $row->transaction?->receipt_number ?? $row->cashRequest?->request_number ?? ($row->cash_transfer_id ? 'TRF-'.$row->cash_transfer_id : ''),
                            $row->journal_at->format('Y-m-d H:i:s'),
                            $row->account_code,
                            $accounting->accountName($row->account_code),
                            $row->debit_amount,
                            $row->credit_amount,
                            $row->description,
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

    private function cashJournalQuery(string $accountCode, ?string $dateFrom, ?string $dateTo, string $search)
    {
        return JurnalAkuntansi::query()
            ->whereIn('account_code', array_keys(AccountingService::presentedAccounts()))
            ->when($accountCode !== '', fn ($query) => $query->where('account_code', $accountCode))
            ->when($dateFrom, fn ($query) => $query->whereDate('journal_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('journal_at', '<=', $dateTo))
            ->when($search !== '', function ($query) use ($search) {
                $transferReference = (string) preg_replace('/^TRF[-\\s]*/i', '', $search);

                $query->where(function ($matches) use ($search, $transferReference) {
                    $matches
                        ->where('journal_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('transaction', fn ($transaction) => $transaction->where('receipt_number', 'like', "%{$search}%"))
                        ->orWhereHas('cashTransfer', fn ($transfer) => $transfer->where('notes', 'like', "%{$search}%"))
                        ->orWhereHas('cashRequest', fn ($cashRequest) => $cashRequest->where('request_number', 'like', "%{$search}%"));

                    if ($transferReference !== '' && ctype_digit($transferReference)) {
                        $matches->orWhere('cash_transfer_id', 'like', "%{$transferReference}%");
                    }
                });
            });
    }
}
