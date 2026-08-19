<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PassbookController extends Controller
{
    public function show(Account $account)
    {
        $account->load('customer');

        $transactions = Transaction::where('account_id', $account->id)
            ->where('status', 'success')
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->paginate(20);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'account' => $account,
                    'transactions' => $transactions,
                ],
            ]);
        }

        return view('teller.passbook.show', compact('account', 'transactions'));
    }

    public function print(Account $account, Request $request)
    {
        $account->load('customer');

        $startLine = max(1, min(20, (int) $request->input('start_line', 1)));
        $limit = 20 - $startLine + 1;

        $page = (int) $request->input('page', 1);

        $transactions = Transaction::where('account_id', $account->id)
            ->where('status', 'success')
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->forPage($page, $limit)
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'account' => $account,
                    'start_line' => $startLine,
                    'transactions' => $transactions,
                ],
            ]);
        }

        return view('teller.passbook.print', compact('account', 'transactions', 'startLine'));
    }
}
