<?php

namespace App\Http\Controllers\Nasabah;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $nasabah = Auth::guard('nasabah')->user();
        $account = $nasabah ? $nasabah->account : null;

        $lastTransaction = $account
            ? Transaction::where('account_id', $account->id)
                ->where('status', 'success')
                ->orderByDesc('transaction_at')
                ->orderByDesc('id')
                ->first()
            : null;

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'nasabah' => $nasabah,
                    'account' => $account,
                    'last_transaction' => $lastTransaction,
                ],
            ]);
        }

        return view('nasabah.dashboard', compact('nasabah', 'account', 'lastTransaction'));
    }
}
