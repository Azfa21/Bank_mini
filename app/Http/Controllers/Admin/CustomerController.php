<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected AccountService $accounts,
    ) {}

    public function index()
    {
        $customers = Customer::with('account')
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($query, $search) {
                $search = trim($search);

                if ($search !== '') {
                    $query->where(function ($sub) use ($search) {
                        $sub->where('full_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%")
                            ->orWhere('class_name', 'like', "%{$search}%");
                    });
                }
            })
            ->latest()
            ->paginate(15);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $customers,
            ]);
        }

        return view('admin.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = DB::transaction(function () use ($request) {
            $created = Customer::create([
                'student_number' => $request->student_number,
                'full_name' => $request->full_name,
                'class_name' => $request->class_name,
                'phone_number' => $request->phone_number,
                'pin_hash' => $request->pin,
                'qr_code_value' => Str::uuid()->toString(),
                'created_by' => $request->user()->id,
                'status' => 'active',
            ]);

            $this->accounts->openFor($created);

            return $created;
        });

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Profil nasabah dan rekening berhasil dibuat.',
                'data' => $customer->load('account'),
            ], 201);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Profil nasabah dan rekening berhasil dibuat.');
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', ['customer' => $customer->load('account')]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $data = $request->only(['student_number', 'full_name', 'class_name', 'phone_number', 'status']);

        if ($request->filled('pin')) {
            $data['pin_hash'] = $request->pin;
        }

        $customer->update($data);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Profil nasabah berhasil diperbarui.',
                'data' => $customer->fresh('account'),
            ]);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Profil nasabah berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Profil nasabah berhasil dihapus.',
            ]);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Profil nasabah berhasil dihapus.');
    }
}
