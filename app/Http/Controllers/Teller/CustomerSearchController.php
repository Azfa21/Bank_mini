<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerSearchController extends Controller
{
    public function search()
    {
        $this->authorize('viewAny', Account::class);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Pencarian nasabah Teller.',
            ]);
        }

        return view('teller.search');
    }

    public function lookup(Request $request)
    {
        $this->authorize('viewAny', Account::class);

        $q = trim($request->input('q', ''));

        if ($q === '') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masukkan nomor rekening atau nilai QR Code terlebih dahulu.',
                ], 422);
            }

            return back()->with('error', 'Masukkan nomor rekening atau nilai QR Code terlebih dahulu.');
        }

        $account = Account::with('customer')
            ->where('account_number', $q)
            ->first();

        if ($account) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => $account,
                ]);
            }

            return redirect()->route('teller.customers.show', $account);
        }

        $customer = Customer::with('account')
            ->where('qr_code_value', $q)
            ->first();

        if ($customer && $customer->account) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => $customer->account,
                ]);
            }

            return redirect()->route('teller.customers.show', $customer->account);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Rekening atau QR Code tidak ditemukan.',
            ], 404);
        }

        return back()->with('error', 'Rekening atau QR Code tidak ditemukan.');
    }

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

        return view('teller.customer-show', compact('account'));
    }
}
