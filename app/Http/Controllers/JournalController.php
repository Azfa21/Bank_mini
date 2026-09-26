<?php

namespace App\Http\Controllers;

use App\Models\JurnalAkuntansi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', JurnalAkuntansi::class);

        $user = Auth::guard('web')->user();
        $userRole = $user ? $user->role : null;
        $indexRoute = $userRole === 'admin'
            ? route('admin.journals.index')
            : route('supervisor.journals.index');
        $journals = JurnalAkuntansi::query()
            ->with('transaction.account.saver')
            ->when($request->input('transaction_number'), function ($query, $number) {
                $query->whereHas('transaction', fn ($q) => $q->where('receipt_number', 'like', "%{$number}%"));
            })
            ->when($request->input('account_code'), fn ($query, $code) => $query->where('account_code', $code))
            ->when($request->input('date_from'), fn ($query, $date) => $query->whereDate('journal_at', '>=', $date))
            ->when($request->input('date_to'), fn ($query, $date) => $query->whereDate('journal_at', '<=', $date))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalDebit = (int) JurnalAkuntansi::query()
            ->when($request->input('transaction_number'), function ($query) use ($request) {
                $query->whereHas('transaction', fn ($q) => $q->where('receipt_number', 'like', "%{$request->input('transaction_number')}%"));
            })
            ->when($request->input('account_code'), fn ($query) => $query->where('account_code', $request->input('account_code')))
            ->when($request->input('date_from'), fn ($query) => $query->whereDate('journal_at', '>=', $request->input('date_from')))
            ->when($request->input('date_to'), fn ($query) => $query->whereDate('journal_at', '<=', $request->input('date_to')))
            ->sum('debit_amount');

        $totalCredit = (int) JurnalAkuntansi::query()
            ->when($request->input('transaction_number'), function ($query) use ($request) {
                $query->whereHas('transaction', fn ($q) => $q->where('receipt_number', 'like', "%{$request->input('transaction_number')}%"));
            })
            ->when($request->input('account_code'), fn ($query) => $query->where('account_code', $request->input('account_code')))
            ->when($request->input('date_from'), fn ($query) => $query->whereDate('journal_at', '>=', $request->input('date_from')))
            ->when($request->input('date_to'), fn ($query) => $query->whereDate('journal_at', '<=', $request->input('date_to')))
            ->sum('credit_amount');

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

        return view('journals.index', compact('journals', 'totalDebit', 'totalCredit', 'userRole', 'indexRoute'));
    }
}
