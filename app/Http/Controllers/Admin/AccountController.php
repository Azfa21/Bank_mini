<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Account $account): View
    {
        $account->load('customer');

        return view('admin.accounts.show', compact('account'));
    }
}
