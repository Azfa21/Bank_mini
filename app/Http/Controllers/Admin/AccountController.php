<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;

class AccountController extends Controller
{
    public function show(Account $account)
    {
        $this->authorize('view', $account);

        $account->load('customer');

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        return view('admin.accounts.show', compact('account'));
    }
}
