<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Customer;

class CustomerRegistrationTest extends TellerTestCase
{
    public function test_teller_can_open_registration_form(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.customers.register'))
            ->assertOk()
            ->assertSee('Registrasi Nasabah');
    }

    public function test_teller_registers_customer_and_account(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.customers.register.store'), [
                'full_name' => 'Budi Santoso',
                'class_name' => 'VII A',
                'phone_number' => '081234567890',
                'email' => 'budi.santoso@example.test',
                'pin' => '654321',
            ])
            ->assertRedirect(route('teller.customers.register'))
            ->assertSessionHas('success');

        $this->assertSame(2, Customer::count());
        $this->assertSame(2, Account::count());

        $customer = Customer::where('full_name', 'Budi Santoso')->firstOrFail();
        $this->assertNotNull($customer->account);
        $this->assertNotNull($customer->student_number);

        $this->assertDatabaseHas('audit_logs', ['action' => 'customer_registered']);
    }

    public function test_registration_requires_six_digit_pin(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.customers.register.store'), [
                'full_name' => 'Budi Santoso',
                'class_name' => 'VII A',
                'email' => 'budi.santoso@example.test',
                'pin' => '123',
            ])
            ->assertSessionHasErrors('pin');

        $this->assertSame(1, Customer::count());
    }

    public function test_admin_cannot_use_teller_registration(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('teller.customers.register.store'), [
                'full_name' => 'Budi Santoso',
                'class_name' => 'VII A',
                'email' => 'budi.santoso@example.test',
                'pin' => '654321',
            ])
            ->assertRedirect(route('login'));

        $this->assertSame(1, Customer::count());
    }
}
