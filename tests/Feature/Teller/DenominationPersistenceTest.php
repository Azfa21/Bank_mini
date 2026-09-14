<?php

namespace Tests\Feature\Teller;

use App\Models\Transaction;
use App\Services\TransactionService;

class DenominationPersistenceTest extends TellerTestCase
{
    public function test_deposit_persists_normalized_denomination_breakdown(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-DEN-001',
                'slip_amount' => 50000,
                'physical_cash_amount' => 50000,
                'denomination_breakdown' => [
                    'kertas_50000' => 1,
                    'kertas_20000' => 0,
                    'tidak_ada' => 5,
                ],
            ])
            ->assertRedirect();

        $transaction = Transaction::where('type', 'deposit')->firstOrFail();

        $this->assertSame(['kertas_50000' => 1], $transaction->denomination_breakdown);
    }

    public function test_withdrawal_persists_denomination_breakdown(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-DEN-002',
                'amount' => 10000,
                'pin' => '123456',
                'denomination_breakdown' => [
                    'kertas_10000' => 1,
                ],
            ])
            ->assertRedirect();

        $transaction = Transaction::where('type', 'withdrawal')->firstOrFail();

        $this->assertSame(['kertas_10000' => 1], $transaction->denomination_breakdown);
    }

    public function test_deposit_without_breakdown_stores_null(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-DEN-003',
                'slip_amount' => 20000,
                'physical_cash_amount' => 20000,
            ])
            ->assertRedirect();

        $transaction = Transaction::where('type', 'deposit')->firstOrFail();

        $this->assertNull($transaction->denomination_breakdown);
    }

    public function test_receipt_shows_cash_breakdown(): void
    {
        $transaction = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-DEN-004',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
            'denomination_breakdown' => ['kertas_50000' => 1],
        ], $this->teller);

        $this->actingAsTeller()
            ->get(route('teller.transactions.receipt', $transaction))
            ->assertOk()
            ->assertSee('Rincian Uang Tunai')
            ->assertSee('Rp 50.000');
    }
}
