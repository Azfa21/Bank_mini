<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Customer;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class TellerTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teller;

    protected User $supervisor;

    protected Customer $nasabah;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->teller = User::create([
            'username' => 'teller1',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Teller Satu',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $this->supervisor = User::create([
            'username' => 'supervisor1',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Supervisor Satu',
            'role' => 'supervisor',
            'status' => 'active',
        ]);

        $this->nasabah = Customer::create([
            'student_number' => '2023001',
            'full_name' => 'Ani Wijaya',
            'class_name' => 'X RPL 1',
            'phone_number' => '081234567001',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-1',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->account = Account::create([
            'account_number' => '2026/2027-001',
            'customer_id' => $this->nasabah->id,
            'balance' => 25000,
            'opening_balance' => 25000,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);

        SystemConfig::updateOrCreate(
            ['config_key' => 'minimum_balance'],
            ['config_value' => '10000']
        );
    }

    protected function actingAsTeller(): static
    {
        return $this->actingAs($this->teller, 'web');
    }
}
