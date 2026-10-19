<?php

namespace App\Http\Controllers;

use App\Models\JurnalAkuntansi;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', JurnalAkuntansi::class);

        $indexRoute = route('admin.journals.index');

        // Alasan: dropdown, filter URL, dan total memakai daftar akun tampil yang sama
        // supaya akun yang disembunyikan tidak bisa muncul lewat kode dari tautan lama.
        $accounts = AccountingService::presentedAccounts();
        $requestedCode = (string) $request->input('account_code', '');
        $accountCode = array_key_exists($requestedCode, $accounts) ? $requestedCode : null;
        $visibleCodes = array_keys($accounts);
        $transactionNumber = $request->input('transaction_number');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $filtered = static function ($query) use ($visibleCodes, $accountCode, $transactionNumber, $dateFrom, $dateTo) {
            return $query
                ->whereIn('account_code', $visibleCodes)
                ->when($transactionNumber, fn ($q, $number) => $q->whereHas('transaction', fn ($sub) => $sub->where('receipt_number', 'like', "%{$number}%")))
                ->when($accountCode, fn ($q, $code) => $q->where('account_code', $code))
                ->when($dateFrom, fn ($q, $date) => $q->whereDate('journal_at', '>=', $date))
                ->when($dateTo, fn ($q, $date) => $q->whereDate('journal_at', '<=', $date));
        };

        $journals = $filtered(JurnalAkuntansi::query()->with('transaction.account.saver'))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalDebit = (int) $filtered(JurnalAkuntansi::query())->sum('debit_amount');
        $totalCredit = (int) $filtered(JurnalAkuntansi::query())->sum('credit_amount');

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'journals' => $journals,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                ],
            ]);
        }

        return view('journals.index', compact('journals', 'totalDebit', 'totalCredit', 'indexRoute', 'accounts', 'accountCode'));
    }
}
