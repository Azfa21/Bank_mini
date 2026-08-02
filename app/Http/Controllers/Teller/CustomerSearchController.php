<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerSearchController extends Controller
{
    public function search(): View
    {
        return view('teller.search');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $q = trim($request->input('q', ''));

        if ($q === '') {
            return back()->with('error', 'Masukkan nomor rekening atau nilai QR Code terlebih dahulu.');
        }

        $account = Account::with('customer')
            ->where('account_number', $q)
            ->first();

        if ($account) {
            return redirect()->route('teller.customers.show', $account);
        }

        $customer = Customer::with('account')
            ->where('qr_code_value', $q)
            ->first();

        if ($customer && $customer->account) {
            return redirect()->route('teller.customers.show', $customer->account);
        }

        return back()->with('error', 'Rekening atau QR Code tidak ditemukan.');
    }

    public function show(Account $account): View
    {
        $account->load('customer');

        return view('teller.customer-show', compact('account'));
    }
}
