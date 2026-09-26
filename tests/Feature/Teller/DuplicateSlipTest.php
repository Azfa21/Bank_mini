<?php

namespace Tests\Feature\Teller;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Database\QueryException;

class DuplicateSlipTest extends TellerTestCase
{
    public function test_same_slip_number_cannot_post_two_deposits(): void
    {
        $payload = [
            'slip_number' => 'SLIP-001',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ];

        $this->actingAsTeller()
            ->postJson(route('teller.deposits.store', $this->account), $payload)
            ->assertCreated();

        $this->actingAsTeller()
            ->postJson(route('teller.deposits.store', $this->account), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertSame(75000, $this->account->refresh()->balance);
        $this->assertSame(1, Transaction::where('slip_number', 'SLIP-001')->count());
    }

    public function test_database_rejects_duplicate_slip_when_service_check_is_bypassed(): void
    {
        app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-001',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        $this->expectException(QueryException::class);

        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => 50000,
            'admin_fee' => 0,
            'balance_before' => 75000,
            'balance_after' => 125000,
            'receipt_number' => 'TRX-20260925-9999',
            'slip_number' => 'SLIP-001',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
            'status' => 'success',
            'transaction_at' => now(),
        ]);
    }
}