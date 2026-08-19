<?php

namespace App\Http\Controllers;

use App\Models\JurnalAkuntansi;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingReportController extends Controller
{
    public const ACCOUNTS = [
        '101' => ['name' => 'Kas Teller (Laci Loket)', 'normal' => 'debit'],
        '102' => ['name' => 'Kas Brankas Utama (Khazanah)', 'normal' => 'debit'],
        '201' => ['name' => 'Tabungan Nasabah (Simpanan Siswa)', 'normal' => 'credit'],
    ];

    public function generalLedger(Request $request)
    {
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

        $normalBalance = self::ACCOUNTS[$accountCode]['normal'] ?? 'debit';
        $endingBalance = $normalBalance === 'debit'
            ? ($totalDebit - $totalCredit)
            : ($totalCredit - $totalDebit);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'account_code' => $accountCode,
                    'account_name' => self::ACCOUNTS[$accountCode]['name'] ?? 'Akun '.$accountCode,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'ending_balance' => $endingBalance,
                    'entries' => $entries,
                ],
            ]);
        }

        $accounts = self::ACCOUNTS;

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
        $date = $request->input('date', now()->toDateString());

        $balances = [];
        $grandDebit = 0;
        $grandCredit = 0;

        foreach (self::ACCOUNTS as $code => $info) {
            $debit = (int) JurnalAkuntansi::where('account_code', $code)
                ->whereDate('journal_at', '<=', $date)
                ->sum('debit_amount');

            $credit = (int) JurnalAkuntansi::where('account_code', $code)
                ->whereDate('journal_at', '<=', $date)
                ->sum('credit_amount');

            $netDebit = 0;
            $netCredit = 0;

            if ($info['normal'] === 'debit') {
                $netDebit = max(0, $debit - $credit);
                $netCredit = max(0, $credit - $debit);
            } else {
                $netCredit = max(0, $credit - $debit);
                $netDebit = max(0, $debit - $credit);
            }

            $balances[$code] = [
                'name' => $info['name'],
                'total_debit' => $debit,
                'total_credit' => $credit,
                'net_debit' => $netDebit,
                'net_credit' => $netCredit,
            ];

            $grandDebit += $netDebit;
            $grandCredit += $netCredit;
        }

        $isBalanced = ($grandDebit === $grandCredit);

        // Calculate Liquidity
        $cashInTeller = $balances['101']['net_debit'] ?? 0;
        $cashInVault = $balances['102']['net_debit'] ?? 0;
        $totalPhysicalCash = $cashInTeller + $cashInVault;
        $totalSavingsObligation = $balances['201']['net_credit'] ?? 0;
        $liquidityRatio = $totalSavingsObligation > 0
            ? round(($totalPhysicalCash / $totalSavingsObligation) * 100, 2)
            : 100.0;

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
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

        return view('reports.trial_balance', compact(
            'date',
            'balances',
            'grandDebit',
            'grandCredit',
            'isBalanced',
            'cashInTeller',
            'cashInVault',
            'totalPhysicalCash',
            'totalSavingsObligation',
            'liquidityRatio'
        ));
    }

    public function exportCsv(Request $request, string $type = 'journals'): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$type.'-'.now()->format('Ymd_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ];

        return response()->stream(function () use ($type, $request) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            if ($type === 'general_ledger') {
                fputcsv($handle, ['No. Jurnal', 'Tanggal', 'Kode Akun', 'Nama Akun', 'Debet (Rp)', 'Kredit (Rp)', 'Keterangan', 'Status']);

                $code = $request->input('account_code');
                $query = JurnalAkuntansi::query()
                    ->when($code, fn ($q) => $q->where('account_code', $code))
                    ->orderBy('journal_at');

                $query->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->journal_number,
                            $row->journal_at?->format('Y-m-d H:i:s'),
                            $row->account_code,
                            self::ACCOUNTS[$row->account_code]['name'] ?? $row->account_code,
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
                        fputcsv($handle, [
                            $row->journal_number,
                            $row->transaction?->receipt_number ?? '-',
                            $row->journal_at?->format('Y-m-d H:i:s'),
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
