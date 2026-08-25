<?php

namespace App\Http\Controllers\Nasabah;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MutasiController extends Controller
{
    public function index(Request $request)
    {
        $nasabah = Auth::guard('nasabah')->user();
        $account = $nasabah ? $nasabah->account : null;

        if (! $account) {
            $transactions = new LengthAwarePaginator([], 0, 15, $request->integer('page', 1));

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'nasabah' => $nasabah,
                        'account' => null,
                        'transactions' => $transactions,
                    ],
                ]);
            }

            return view('nasabah.mutasi', compact('nasabah', 'account', 'transactions'));
        }

        $transactions = Transaction::query()
            ->where(function ($query) use ($account) {
                $query->where('account_id', $account->id)
                    ->orWhere('recipient_account_id', $account->id);
            })
            ->where('status', 'success')
            ->when($request->input('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->input('date_from'), fn ($query, $date) => $query->whereDate('transaction_at', '>=', $date))
            ->when($request->input('date_to'), fn ($query, $date) => $query->whereDate('transaction_at', '<=', $date))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'nasabah' => $nasabah,
                    'account' => $account,
                    'transactions' => $transactions,
                ],
            ]);
        }

        return view('nasabah.mutasi', compact('nasabah', 'account', 'transactions'));
    }

    public function statement(Request $request)
    {
        $nasabah = Auth::guard('nasabah')->user();
        $account = $nasabah ? $nasabah->account : null;

        if (! $account) {
            abort(404, 'Rekening tidak ditemukan.');
        }

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $transactions = Transaction::query()
            ->where(function ($query) use ($account) {
                $query->where('account_id', $account->id)
                    ->orWhere('recipient_account_id', $account->id);
            })
            ->where('status', 'success')
            ->whereDate('transaction_at', '>=', $dateFrom)
            ->whereDate('transaction_at', '<=', $dateTo)
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get();

        $summary = collect(DB::table('transactions')
            ->where(function ($query) use ($account) {
                $query->where('account_id', $account->id)
                    ->orWhere('recipient_account_id', $account->id);
            })
            ->where('status', 'success')
            ->whereDate('transaction_at', '>=', $dateFrom)
            ->whereDate('transaction_at', '<=', $dateTo)
            ->selectRaw(
                'type, SUM(CASE WHEN account_id = ? THEN amount ELSE 0 END) as total_out, SUM(CASE WHEN recipient_account_id = ? THEN amount ELSE 0 END) as total_in',
                [$account->id, $account->id]
            )
            ->groupBy('type')
            ->get())
            ->mapWithKeys(fn ($row) => [
                $row->type => ['out' => (int) $row->total_out, 'in' => (int) $row->total_in],
            ]);

        $totalDeposit = $summary['deposit']['out'] ?? 0;
        $totalWithdrawal = $summary['withdrawal']['out'] ?? 0;
        $totalTransferOut = $summary['transfer']['out'] ?? 0;
        $totalTransferIn = $summary['transfer']['in'] ?? 0;

        $verificationCode = 'EST-'.strtoupper(substr(
            hash('sha256', $account->id.'|'.$dateFrom.'|'.$dateTo.'|'.$transactions->count().'|'.$totalDeposit.'|'.$totalWithdrawal),
            0,
            12
        ));

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'nasabah' => $nasabah,
                    'account' => $account,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'transactions' => $transactions,
                    'verification_code' => $verificationCode,
                    'summary' => [
                        'total_deposit' => $totalDeposit,
                        'total_withdrawal' => $totalWithdrawal,
                        'total_transfer_out' => $totalTransferOut,
                        'total_transfer_in' => $totalTransferIn,
                    ],
                ],
            ]);
        }

        return view('nasabah.statement', compact(
            'nasabah',
            'account',
            'transactions',
            'dateFrom',
            'dateTo',
            'totalDeposit',
            'totalWithdrawal',
            'totalTransferOut',
            'totalTransferIn',
            'verificationCode'
        ));
    }
}
