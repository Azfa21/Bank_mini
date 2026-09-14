<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class SearchEdgeCasesTest extends TellerTestCase
{
    protected function createCustomerWithAccount(
        string $name,
        string $studentNumber,
        string $accountNumber,
        string $className = 'X RPL 2'
    ): Account {
        $customer = Customer::create([
            'student_number' => $studentNumber,
            'full_name' => $name,
            'class_name' => $className,
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-'.$studentNumber,
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        return Account::create([
            'account_number' => $accountNumber,
            'customer_id' => $customer->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }

    public function test_search_by_class_name_lists_results(): void
    {
        $this->createCustomerWithAccount('Budi Santoso', '2023010', '2026/2027-010', 'X RPL 1');

        $this->actingAsTeller()
            ->get(route('teller.customers.lookup', ['q' => 'X RPL 1']))
            ->assertOk()
            ->assertSee('Hasil Pencarian')
            ->assertSee('Ani Wijaya')
            ->assertSee('Budi Santoso');
    }

    public function test_search_is_case_insensitive(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.customers.lookup', ['q' => 'ani wijaya']))
            ->assertRedirect(route('teller.customers.show', $this->account));
    }

    public function test_search_limits_results_to_twenty_five(): void
    {
        foreach (range(1, 30) as $i) {
            $this->createCustomerWithAccount(
                'Siswa '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                '20233'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                '2026/310'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            );
        }

        $response = $this->actingAsTeller()
            ->get(route('teller.customers.lookup', ['q' => 'Siswa']))
            ->assertOk()
            ->assertSee('Hasil Pencarian');

        $this->assertSame(25, $response->viewData('results')->count());
    }

    public function test_lookup_json_returns_single_account(): void
    {
        $this->actingAsTeller()
            ->getJson(route('teller.customers.lookup', ['q' => '2023001']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account_number', '2026/2027-001');
    }

    public function test_lookup_json_returns_multiple_matches(): void
    {
        $this->createCustomerWithAccount('Anisah Putri', '2023011', '2026/2027-011');

        $this->actingAsTeller()
            ->getJson(route('teller.customers.lookup', ['q' => 'Ani']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }
}
