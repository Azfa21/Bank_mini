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

        if ($this->wantsJson()) {
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
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Masukkan nomor rekening, NIS, nama, atau nilai QR Code terlebih dahulu.',
                ], 422);
            }

            return back()->with('error', 'Masukkan nomor rekening, NIS, nama, atau nilai QR Code terlebih dahulu.');
        }

        $account = Account::with('customer')
            ->where('account_number', $q)
            ->first();

        if ($account) {
            return $this->redirectToAccount($request, $account);
        }

        $customer = Customer::with('account')
            ->where('qr_code_value', $q)
            ->first();

        if ($customer && $customer->account) {
            return $this->redirectToAccount($request, $customer->account);
        }

        $results = Account::with('customer')
            ->where('account_number', 'like', "%{$q}%")
            ->orWhereHas('customer', function ($query) use ($q) {
                $query->where('student_number', 'like', "%{$q}%")
                    ->orWhere('full_name', 'like', "%{$q}%")
                    ->orWhere('class_name', 'like', "%{$q}%");
            })
            ->orderBy('account_number')
            ->limit(25)
            ->get();

        if ($results->count() === 1) {
            return $this->redirectToAccount($request, $results->first());
        }

        if ($results->isNotEmpty()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'data' => $results,
                ]);
            }

            return view('teller.search-results', ['q' => $q, 'results' => $results]);
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Nasabah tidak ditemukan.',
            ], 404);
        }

        return back()->with('error', 'Nasabah tidak ditemukan.');
    }

    protected function redirectToAccount(Request $request, Account $account)
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        return redirect()->route('teller.customers.show', $account);
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);

        $account->load('customer');

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        return view('teller.customer-show', compact('account'));
    }
}
