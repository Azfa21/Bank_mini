<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class SearchByNameTest extends TellerTestCase
{
    protected function createCustomerWithAccount(string $name, string $studentNumber, string $accountNumber): Account
    {
        $customer = Customer::create([
            'student_number' => $studentNumber,
            'full_name' => $name,
            'class_name' => 'X RPL 2',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-'.$studentNumber,
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        return Account::create([
            'account_number' => $accountNumber,
            'customer_id' => $customer->id,
            'balance' => 5000,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }

    public function test_search_by_nis_redirects_to_profile(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.customers.lookup', ['q' => '2023001']))
            ->assertRedirect(route('teller.customers.show', $this->account));
    }

    public function test_search_by_partial_name_redirects_when_unique(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.customers.lookup', ['q' => 'Ani Wijaya']))
            ->assertRedirect(route('teller.customers.show', $this->account));
    }

    public function test_search_by_partial_name_lists_multiple_matches(): void
    {
        $this->createCustomerWithAccount('Anisah Putri', '2023002', '2026/2027-002');

        $this->actingAsTeller()
            ->get(route('teller.customers.lookup', ['q' => 'Ani']))
            ->assertOk()
            ->assertSee('Hasil Pencarian')
            ->assertSee('Ani Wijaya')
            ->assertSee('Anisah Putri');
    }

    public function test_search_page_has_registration_link(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.customers.search'))
            ->assertOk()
            ->assertSee('Registrasi Nasabah');
    }
}
