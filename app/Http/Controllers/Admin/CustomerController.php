<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Account;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::with('account')
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create([
            'student_number' => $request->student_number,
            'full_name' => $request->full_name,
            'class_name' => $request->class_name,
            'phone_number' => $request->phone_number,
            'pin_hash' => $request->pin,
            'qr_code_value' => Str::uuid()->toString(),
            'created_by' => $request->user()->id,
            'status' => 'active',
        ]);

        $academicYear = date('Y').'/'.(date('Y') + 1);
        $lastAccount = Account::where('academic_year', $academicYear)->count();

        Account::create([
            'account_number' => $academicYear.'-'.str_pad($lastAccount + 1, 3, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => $academicYear,
            'status' => 'active',
            'opened_at' => now(),
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Profil nasabah dan rekening berhasil dibuat.');
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', ['customer' => $customer->load('account')]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->only(['student_number', 'full_name', 'class_name', 'phone_number', 'status']);

        if ($request->filled('pin')) {
            $data['pin_hash'] = $request->pin;
        }

        $customer->update($data);

        return redirect()->route('admin.customers.index')->with('success', 'Profil nasabah berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Profil nasabah berhasil dihapus.');
    }
}
