<?php

namespace Tests\Feature\Teller;

use App\Models\SystemConfig;
use App\Models\Transaction;

class WithdrawalLimitTest extends TellerTestCase
{
    public function test_withdrawal_rejected_above_max_without_order(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'max_withdrawal_without_order'], ['config_value' => '10000']);

        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-LIM-001',
                'amount' => 15000,
                'pin' => '123456',
            ])
            ->assertRedirect(route('teller.withdrawals.create', $this->account))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());
    }

    public function test_withdrawal_allowed_when_amount_equals_limit(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'max_withdrawal_without_order'], ['config_value' => '10000']);

        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-LIM-002',
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertRedirect();

        $transaction = Transaction::where('type', 'withdrawal')->firstOrFail();
        $this->assertSame(10000, $transaction->amount);
        $this->assertSame(15000, $this->account->refresh()->balance);
    }
}
