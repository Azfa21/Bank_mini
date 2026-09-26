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

        if ($this->wantsJson()) {
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

    public function printCard()
    {
        $nasabah = Auth::guard('nasabah')->user();
        $account = $nasabah?->account;

        if (! $account) {
            if ($this->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum memiliki rekening aktif.',
                ], 404);
            }

            return redirect()
                ->route('nasabah.dashboard')
                ->with('error', 'Anda belum memiliki rekening aktif.');
        }

        $account->load('saver');

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        return view('cards.print', [
            'account' => $account,
            'sectionLabel' => 'Nasabah',
            'backUrl' => route('nasabah.dashboard'),
        ]);
    }
}
