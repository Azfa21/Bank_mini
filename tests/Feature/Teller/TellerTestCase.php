<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Saver;
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

    protected User $admin2;

    protected Saver $nasabah;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->teller = User::create([
            'username' => 'teller1',
            'email' => 'teller1@example.test',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Teller Satu',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $this->admin2 = User::create([
            'username' => 'admin2',
            'email' => 'admin2@example.test',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Admin Dua',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->nasabah = Saver::create([
            'student_number' => '2023001',
            'full_name' => 'Ani Wijaya',
            'class_name' => 'X RPL 1',
            'phone_number' => '081234567001',
            'email' => 'ani.wijaya@example.test',
            'pin_hash' => Hash::make('123456'),
            'password_hash' => Hash::make('password123'),
            'qr_code_value' => 'qr-test-1',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->account = Account::create([
            'account_number' => '2026/2027-001',
            'saver_id' => $this->nasabah->id,
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
