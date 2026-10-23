<?php

namespace Tests\Feature\Mobile;

use App\Models\Account;
use App\Models\Saver;
use App\Services\TransactionService;

class MobileMutasiTest extends MobileTestCase
{
    public function test_mutasi_returns_transactions_with_pagination_meta(): void
    {
        $this->deposit(50000);

        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.mutasi'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transactions.0.type', 'deposit')
            ->assertJsonPath('data.transactions.0.direction', 'in')
            ->assertJsonStructure(['data' => ['meta' => ['current_page', 'last_page', 'per_page', 'total']]]);
    }

    public function test_mutasi_filters_by_type(): void
    {
        $this->deposit(50000);

        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.mutasi', ['type' => 'withdrawal']))
            ->assertOk()
            ->assertJsonPath('data.transactions', []);
    }

    public function test_mutasi_does_not_leak_other_customer_transactions(): void
    {
        $otherCustomer = Saver::create([
            'student_number' => '2023002',
            'full_name' => 'Budi Santoso',
            'class_name' => 'X RPL 2',
            'phone_number' => '081234567002',
            'pin_hash' => bcrypt('123456'),
            'qr_code_value' => 'qr-test-2',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $otherAccount = Account::create([
            'account_number' => '2026/2027-002',
            'saver_id' => $otherCustomer->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);

        app(TransactionService::class)->deposit($otherAccount, [
            'slip_number' => 'SLIP-OTHER-001',
            'slip_amount' => 99000,
            'amount' => 99000,
        ], $this->teller);

        $token = $this->loginAndGetToken();

        $response = $this->withMobileToken($token)
            ->getJson(route('api.mobile.mutasi'))
            ->assertOk();

        $this->assertSame(0, $response->json('data.meta.total'));
    }

    public function test_statement_returns_summary_and_verification_code(): void
    {
        $this->deposit(50000);

        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.statement'))
            ->assertOk()
            ->assertJsonPath('data.summary.total_deposit', 50000)
            ->assertJsonPath('data.account.account_number', '2026/2027-001')
            ->assertJsonStructure(['data' => ['verification_code', 'date_from', 'date_to']]);
    }

    public function test_statement_without_account_returns_not_found(): void
    {
        $customer = Saver::create([
            'student_number' => '2023997',
            'full_name' => 'Tanpa Rekening Dua',
            'class_name' => 'X RPL 3',
            'phone_number' => '081234567997',
            'pin_hash' => bcrypt('123456'),
            'password_hash' => bcrypt('password123'),
            'qr_code_value' => 'qr-mobile-no-account-2',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $token = (string) $this->postJson(route('api.mobile.login'), [
            'student_number' => $customer->student_number,
            'password' => 'password123',
        ])->json('data.token');

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.statement'))
            ->assertStatus(404);
    }
}
