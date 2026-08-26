<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Teller\TellerTestCase;

class CustomerBlockTest extends TellerTestCase
{
    public function test_admin_can_block_and_unblock_customer(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.customers.toggle-status', $this->nasabah))
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success', 'Nasabah Ani Wijaya berhasil diblokir.');

        $this->assertDatabaseHas('customers', ['id' => $this->nasabah->id, 'status' => 'blocked']);

        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.customers.toggle-status', $this->nasabah))
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success', 'Nasabah Ani Wijaya berhasil diaktifkan.');

        $this->assertDatabaseHas('customers', ['id' => $this->nasabah->id, 'status' => 'active']);
    }

    public function test_toggle_status_via_api_returns_json(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patchJson("/api/admin/customers/{$this->nasabah->id}/toggle-status")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Nasabah Ani Wijaya berhasil diblokir.',
            ]);

        $this->assertDatabaseHas('customers', ['id' => $this->nasabah->id, 'status' => 'blocked']);
    }

    public function test_non_admin_cannot_toggle_customer_status(): void
    {
        $this->actingAsTeller()
            ->patchJson("/api/admin/customers/{$this->nasabah->id}/toggle-status")
            ->assertForbidden();
    }

    public function test_customer_delete_endpoint_is_removed(): void
    {
        $this->actingAs($this->admin, 'web')
            ->delete("/admin/customers/{$this->nasabah->id}")
            ->assertStatus(405);

        $this->assertDatabaseHas('customers', ['id' => $this->nasabah->id]);
    }

    public function test_deposit_rejected_for_blocked_customer(): void
    {
        $this->nasabah->update(['status' => 'blocked']);

        $this->actingAsTeller()
            ->from(route('teller.deposits.create', $this->account))
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-BLK-001',
                'slip_amount' => 50000,
                'physical_cash_amount' => 50000,
            ])
            ->assertRedirect(route('teller.deposits.create', $this->account))
            ->assertSessionHas('error', 'Nasabah dalam status diblokir.');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_withdrawal_rejected_for_blocked_customer(): void
    {
        $this->nasabah->update(['status' => 'blocked']);

        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-BLK-002',
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertRedirect(route('teller.withdrawals.create', $this->account))
            ->assertSessionHas('error', 'Nasabah dalam status diblokir.');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_transfer_from_blocked_customer_is_rejected(): void
    {
        $destination = $this->createDestination();
        $this->nasabah->update(['status' => 'blocked']);

        $this->actingAsTeller()
            ->post(route('teller.transfers.store', $this->account), [
                'recipient_account_number' => $destination->account_number,
                'amount' => 5000,
                'pin' => '123456',
                'notes' => 'Transfer blokir sumber',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Nasabah dalam status diblokir.');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
        $this->assertSame(10000, $destination->refresh()->balance);
    }

    public function test_transfer_to_blocked_destination_is_rejected(): void
    {
        $destination = $this->createDestination();
        $destination->customer->update(['status' => 'blocked']);

        $this->actingAsTeller()
            ->post(route('teller.transfers.store', $this->account), [
                'recipient_account_number' => $destination->account_number,
                'amount' => 5000,
                'pin' => '123456',
                'notes' => 'Transfer blokir tujuan',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Nasabah dalam status diblokir.');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
        $this->assertSame(10000, $destination->refresh()->balance);
    }

    private function createDestination(): Account
    {
        $customer = Customer::create([
            'student_number' => '2023002',
            'full_name' => 'Budi Santoso',
            'class_name' => 'X RPL 2',
            'phone_number' => '081234567002',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-2',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        return Account::create([
            'account_number' => '2026/2027-002',
            'customer_id' => $customer->id,
            'balance' => 10000,
            'opening_balance' => 10000,
            'minimum_balance' => 10000,
            'academic_year' => now()->format('Y').'/'.(now()->format('Y') + 1),
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }
}
