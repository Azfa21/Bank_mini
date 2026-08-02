<?php

namespace Tests\Feature\Teller;

use App\Models\JurnalAkuntansi;
use App\Models\Transaction;

class WithdrawalTest extends TellerTestCase
{
    public function test_withdrawal_success_updates_balance_creates_transaction_and_journal(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-010',
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertRedirect();

        $transaction = Transaction::where('type', 'withdrawal')->firstOrFail();

        $this->assertSame('success', $transaction->status);
        $this->assertSame(10000, $transaction->amount);
        $this->assertSame(25000, $transaction->balance_before);
        $this->assertSame(15000, $transaction->balance_after);
        $this->assertSame('SLIP-010', $transaction->slip_number);
        $this->assertNull($transaction->physical_cash_amount);

        $this->assertSame(15000, $this->account->refresh()->balance);

        $this->assertSame(2, $transaction->journalEntries()->count());
        $this->assertSame(10000, JurnalAkuntansi::where('transaction_id', $transaction->id)->sum('debit_amount'));
        $this->assertSame(10000, JurnalAkuntansi::where('transaction_id', $transaction->id)->sum('credit_amount'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_withdrawal_success',
        ]);
    }

    public function test_withdrawal_rejects_wrong_pin(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-011',
                'amount' => 10000,
                'pin' => '000000',
            ])
            ->assertRedirect(route('teller.withdrawals.create', $this->account))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_withdrawal_rejected',
        ]);
    }

    public function test_withdrawal_rejects_insufficient_balance(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-012',
                'amount' => 30000,
                'pin' => '123456',
            ])
            ->assertRedirect(route('teller.withdrawals.create', $this->account))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_withdrawal_rejects_when_balance_after_below_minimum(): void
    {
        $this->account->update(['balance' => 15000]);

        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-013',
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertRedirect(route('teller.withdrawals.create', $this->account))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(15000, $this->account->refresh()->balance);
    }

    public function test_withdrawal_rejects_non_six_digit_pin(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-014',
                'amount' => 10000,
                'pin' => '12345',
            ])
            ->assertSessionHasErrors('pin');

        $this->assertSame(0, Transaction::count());
    }

    public function test_withdrawal_rejects_zero_amount(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-015',
                'amount' => 0,
                'pin' => '123456',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Transaction::count());
    }

    public function test_admin_cannot_post_withdrawal(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-016',
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Transaction::count());
    }
}
