<?php

namespace Tests\Feature\Teller;

use App\Models\JurnalAkuntansi;
use App\Models\Transaction;

class DepositTest extends TellerTestCase
{
    public function test_deposit_success_updates_balance_creates_transaction_and_journal(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-001',
                'slip_amount' => 100000,
                'physical_cash_amount' => 100000,
            ])
            ->assertRedirect();

        $transaction = Transaction::where('type', 'deposit')->firstOrFail();

        $this->assertSame('success', $transaction->status);
        $this->assertSame(100000, $transaction->amount);
        $this->assertSame(100000, $transaction->slip_amount);
        $this->assertSame(100000, $transaction->physical_cash_amount);
        $this->assertSame(25000, $transaction->balance_before);
        $this->assertSame(125000, $transaction->balance_after);
        $this->assertSame('SLIP-001', $transaction->slip_number);
        $this->assertNotNull($transaction->receipt_number);

        $this->assertSame(125000, $this->account->refresh()->balance);

        $this->assertSame(2, $transaction->journalEntries()->count());
        $this->assertSame(100000, JurnalAkuntansi::where('transaction_id', $transaction->id)->sum('debit_amount'));
        $this->assertSame(100000, JurnalAkuntansi::where('transaction_id', $transaction->id)->sum('credit_amount'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_deposit_success',
            'entity_name' => 'transaction',
            'entity_id' => $transaction->id,
        ]);
    }

    public function test_deposit_rejects_when_slip_amount_differs_from_physical_cash(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.deposits.create', $this->account))
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-002',
                'slip_amount' => 100000,
                'physical_cash_amount' => 95000,
            ])
            ->assertRedirect(route('teller.deposits.create', $this->account))
            ->assertSessionHasErrors('physical_cash_amount');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_deposit_rejects_zero_amount(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.deposits.create', $this->account))
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-003',
                'slip_amount' => 0,
                'physical_cash_amount' => 0,
            ])
            ->assertSessionHasErrors('slip_amount');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_deposit_rejected_on_inactive_account(): void
    {
        $this->account->update(['status' => 'closed']);

        $this->actingAsTeller()
            ->from(route('teller.deposits.create', $this->account))
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-004',
                'slip_amount' => 50000,
                'physical_cash_amount' => 50000,
            ])
            ->assertRedirect(route('teller.deposits.create', $this->account))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_deposit_rejected',
        ]);
    }

    public function test_admin_cannot_post_deposit(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-005',
                'slip_amount' => 10000,
                'physical_cash_amount' => 10000,
            ])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Transaction::count());
    }
}
