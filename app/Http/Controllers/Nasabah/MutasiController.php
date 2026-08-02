<?php

namespace App\Http\Controllers\Nasabah;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MutasiController extends Controller
{
    public function index(Request $request): View
    {
        $nasabah = Auth::guard('nasabah')->user();
        $account = $nasabah->account;

        if (! $account) {
            $transactions = new LengthAwarePaginator([], 0, 15, $request->integer('page', 1));

            return view('nasabah.mutasi', compact('nasabah', 'account', 'transactions'));
        }

        $transactions = Transaction::query()
            ->where('account_id', $account->id)
            ->where('status', 'success')
            ->when($request->input('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->input('date_from'), fn ($query, $date) => $query->whereDate('transaction_at', '>=', $date))
            ->when($request->input('date_to'), fn ($query, $date) => $query->whereDate('transaction_at', '<=', $date))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('nasabah.mutasi', compact('nasabah', 'account', 'transactions'));
    }
}
