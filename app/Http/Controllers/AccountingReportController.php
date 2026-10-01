<?php

namespace App\Http\Controllers;

use App\Models\JurnalAkuntansi;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingReportController extends Controller
{
    public function __construct(
        protected AccountingService $accounting,
    ) {}

    public function generalLedger(Request $request)
    {
        $this->authorize('viewAny', JurnalAkuntansi::class);

        $accountCode = $request->input('account_code', '101');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = JurnalAkuntansi::query()
            ->when($accountCode, fn ($q) => $q->where('account_code', $accountCode))
            ->when($dateFrom, fn ($q) => $q->whereDate('journal_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('journal_at', '<=', $dateTo))
            ->orderBy('journal_at')
            ->orderBy('id');

        $entries = $query->paginate(25)->withQueryString();

        $totalDebit = (int) (clone $query)->sum('debit_amount');
        $totalCredit = (int) (clone $query)->sum('credit_amount');

        $normalBalance = AccountingService::CHART_OF_ACCOUNTS[$accountCode]['normal'] ?? 'debit';
        $endingBalance = $normalBalance === 'debit'
            ? ($totalDebit - $totalCredit)
            : ($totalCredit - $totalDebit);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'account_code' => $accountCode,
                    'account_name' => $this->accounting->accountName($accountCode),
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'ending_balance' => $endingBalance,
                    'entries' => $entries,
                ],
            ]);
        }

        $accounts = AccountingService::CHART_OF_ACCOUNTS;

        return view('reports.general_ledger', compact(
            'entries',
            'accountCode',
            'accounts',
            'totalDebit',
            'totalCredit',
            'endingBalance',
            'dateFrom',
            'dateTo'
        ));
    }

    public function trialBalance(Request $request)
    {
        $this->authorize('viewAny', JurnalAkuntansi::class);

        $trialBalance = $this->accounting->trialBalance(
            $request->input('date', now()->toDateString())
        );

        $balances = $trialBalance['balances'];
        $grandDebit = $trialBalance['grand_debit'];
        $grandCredit = $trialBalance['grand_credit'];
        $isBalanced = $trialBalance['is_balanced'];

        // Calculate Liquidity
        $cashInTeller = $balances['101']['net_debit'];
        $cashInVault = $balances['102']['net_debit'];
        $totalPhysicalCash = $cashInTeller + $cashInVault;
        $totalSavingsObligation = $balances['201']['net_credit'];
        $liquidityRatio = $totalSavingsObligation > 0
            ? round(($totalPhysicalCash / $totalSavingsObligation) * 100, 2)
            : 100.0;

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $trialBalance['date'],
                    'balances' => $balances,
                    'grand_debit' => $grandDebit,
                    'grand_credit' => $grandCredit,
                    'is_balanced' => $isBalanced,
                    'liquidity' => [
                        'cash_in_teller' => $cashInTeller,
                        'cash_in_vault' => $cashInVault,
                        'total_physical_cash' => $totalPhysicalCash,
                        'total_savings' => $totalSavingsObligation,
                        'liquidity_ratio_percent' => $liquidityRatio,
                    ],
                ],
            ]);
        }

        return view('reports.trial_balance', [
            'date' => $trialBalance['date'],
            'balances' => $balances,
            'grandDebit' => $grandDebit,
            'grandCredit' => $grandCredit,
            'isBalanced' => $isBalanced,
            'cashInTeller' => $cashInTeller,
            'cashInVault' => $cashInVault,
            'totalPhysicalCash' => $totalPhysicalCash,
            'totalSavingsObligation' => $totalSavingsObligation,
            'liquidityRatio' => $liquidityRatio,
        ]);
    }

    public function incomeStatement(Request $request)
    {
        $this->authorize('viewAny', JurnalAkuntansi::class);

        $statement = $this->accounting->incomeStatement(
            $request->input('date', now()->toDateString())
        );

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => $statement,
            ]);
        }

        return view('reports.income_statement', [
            'date' => $statement['date'],
            'incomes' => $statement['incomes'],
            'totalIncome' => $statement['total_income'],
            'netIncome' => $statement['net_income'],
        ]);
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
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            if ($type === 'general_ledger') {
                fputcsv($handle, ['No. Jurnal', 'Tanggal', 'Kode Akun', 'Nama Akun', 'Debet (Rp)', 'Kredit (Rp)', 'Keterangan', 'Status']);

                $code = $request->input('account_code');
                $query = JurnalAkuntansi::query()
                    ->when($code, fn ($q) => $q->where('account_code', $code))
                    ->orderBy('journal_at');

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
            } else {
                fputcsv($handle, ['No. Jurnal', 'No. Transaksi', 'Tanggal', 'Kode Akun', 'Tipe', 'Debet (Rp)', 'Kredit (Rp)', 'Keterangan']);

                JurnalAkuntansi::with('transaction')->orderBy('id')->chunk(500, function ($rows) use ($handle) {
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
