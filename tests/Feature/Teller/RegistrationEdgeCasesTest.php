<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Customer;

class RegistrationEdgeCasesTest extends TellerTestCase
{
    protected function register(string $name, string $pin = '112233'): void
    {
        $this->actingAsTeller()
            ->post(route('teller.customers.register.store'), [
                'full_name' => $name,
                'class_name' => 'VII B',
                'pin' => $pin,
            ])
            ->assertRedirect(route('teller.customers.register'));
    }

    public function test_registration_generates_unique_nis_and_account_numbers(): void
    {
        $this->register('Budi Santoso');
        $this->register('Citra Lestari');

        $this->assertSame(3, Customer::count());
        $this->assertSame(3, Account::count());

        $this->assertSame(
            Customer::pluck('student_number')->unique()->count(),
            Customer::count()
        );

        $this->assertSame(
            Account::pluck('account_number')->unique()->count(),
            Account::count()
        );
    }

    public function test_new_customer_can_login_with_pin(): void
    {
        $this->register('Budi Santoso', '445566');

        $customer = Customer::where('full_name', 'Budi Santoso')->firstOrFail();

        $this->post(route('login.submit'), [
            'identifier' => $customer->student_number,
            'secret' => '445566',
        ])->assertRedirect(route('nasabah.dashboard'));

        $this->assertAuthenticatedAs($customer, 'nasabah');
    }

    public function test_registration_requires_name_class_and_pin(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.customers.register.store'), [])
            ->assertSessionHasErrors(['full_name', 'class_name', 'pin']);

        $this->assertSame(1, Customer::count());
    }

    public function test_registration_json_returns_created_account(): void
    {
        $this->actingAsTeller()
            ->postJson(route('teller.customers.register.store'), [
                'full_name' => 'Budi Santoso',
                'class_name' => 'VII B',
                'pin' => '112233',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'Budi Santoso')
            ->assertJsonPath('data.account.account_number', fn ($value) => is_string($value) && $value !== '');
    }
}
