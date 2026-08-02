<?php

namespace App\Http\Controllers\Nasabah;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $nasabah = Auth::guard('nasabah')->user();
        $account = $nasabah->account;

        $lastTransaction = $account
            ? Transaction::where('account_id', $account->id)
                ->where('status', 'success')
                ->orderByDesc('transaction_at')
                ->orderByDesc('id')
                ->first()
            : null;

        return view('nasabah.dashboard', compact('nasabah', 'account', 'lastTransaction'));
    }
}
