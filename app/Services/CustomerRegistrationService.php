<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerRegistrationService
{
    public function __construct(
        protected AccountService $accounts,
        protected SequenceService $sequences,
    ) {}

    /**
     * @param  array{full_name: string, class_name: string, phone_number?: string|null, email?: string|null, pin: string}  $data
     */
    public function register(array $data, ?int $createdBy = null): Customer
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $year = now()->format('Y');

            do {
                $candidate = sprintf('%s%04d', $year, $this->sequences->next("NIS-{$year}"));
            } while (Customer::where('student_number', $candidate)->exists());

            $customer = Customer::create([
                'student_number' => $candidate,
                'full_name' => $data['full_name'],
                'class_name' => $data['class_name'],
                'phone_number' => $data['phone_number'] ?? null,
                'email' => isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) : null,
                'pin_hash' => $data['pin'],
                'qr_code_value' => Str::uuid()->toString(),
                'created_by' => $createdBy,
                'status' => 'active',
            ]);

            $this->accounts->openFor($customer);

            AuditLogService::record('customer_registered', 'customer', $customer->id, [
                'student_number' => $customer->student_number,
                'full_name' => $customer->full_name,
                'class_name' => $customer->class_name,
            ], $createdBy);

            return $customer;
        });
    }
}
