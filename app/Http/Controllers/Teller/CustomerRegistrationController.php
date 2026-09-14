<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\StoreCustomerRegistrationRequest;
use App\Models\Account;
use App\Services\CustomerRegistrationService;
use Illuminate\View\View;

class CustomerRegistrationController extends Controller
{
    public function __construct(
        protected CustomerRegistrationService $registrations,
    ) {}

    public function create(): View
    {
        $this->authorize('viewAny', Account::class);

        return view('teller.customer-register');
    }

    public function store(StoreCustomerRegistrationRequest $request)
    {
        $this->authorize('viewAny', Account::class);

        $customer = $this->registrations->register([
            'full_name' => $request->validated('full_name'),
            'class_name' => $request->validated('class_name'),
            'phone_number' => $request->validated('phone_number'),
            'pin' => $request->validated('pin'),
        ], $request->user()->id);

        $customer->load('account');

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Nasabah dan rekening berhasil didaftarkan.',
                'data' => $customer,
            ], 201);
        }

        return redirect()->route('teller.customers.register')
            ->with('success', 'Nasabah '.$customer->full_name.' berhasil didaftarkan.')
            ->with('registration', [
                'full_name' => $customer->full_name,
                'student_number' => $customer->student_number,
                'account_number' => $customer->account?->account_number,
            ]);
    }
}
