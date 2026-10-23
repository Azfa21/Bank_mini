<?php

namespace Tests\Feature\Mobile;

use App\Models\Saver;

class MobileDashboardTest extends MobileTestCase
{
    public function test_dashboard_returns_balance_and_bank_info(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.dashboard'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account.account_number', '2026/2027-001')
            ->assertJsonPath('data.account.balance', 25000)
            ->assertJsonPath('data.customer.full_name', 'Ani Wijaya')
            ->assertJsonStructure(['data' => ['bank' => ['name']]]);
    }

    public function test_dashboard_returns_last_transaction(): void
    {
        $this->deposit(50000);

        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.last_transaction.type', 'deposit')
            ->assertJsonPath('data.last_transaction.direction', 'in')
            ->assertJsonPath('data.last_transaction.amount', 50000);
    }

    public function test_dashboard_without_account_returns_null(): void
    {
        $customer = Saver::create([
            'student_number' => '2023998',
            'full_name' => 'Tanpa Rekening',
            'class_name' => 'X RPL 2',
            'phone_number' => '081234567998',
            'pin_hash' => bcrypt('123456'),
            'password_hash' => bcrypt('password123'),
            'qr_code_value' => 'qr-mobile-no-account',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $token = (string) $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023998',
            'password' => 'password123',
        ])->json('data.token');

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.account', null)
            ->assertJsonPath('data.last_transaction', null);

        $this->assertNotNull($customer->id);
    }

    public function test_card_returns_qr_value(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.card'))
            ->assertOk()
            ->assertJsonPath('data.qr_code_value', 'qr-test-1')
            ->assertJsonPath('data.account_number', '2026/2027-001');
    }
}
