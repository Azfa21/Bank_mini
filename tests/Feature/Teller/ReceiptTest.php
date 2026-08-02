<?php

namespace Tests\Feature\Teller;

use App\Models\Transaction;

class ReceiptTest extends TellerTestCase
{
    protected function createDepositTransaction(): Transaction
    {
        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-020',
                'slip_amount' => 50000,
                'physical_cash_amount' => 50000,
            ]);

        return Transaction::where('type', 'deposit')->firstOrFail();
    }

    public function test_receipt_shows_transaction_details(): void
    {
        $transaction = $this->createDepositTransaction();

        $this->actingAsTeller()
            ->get(route('teller.transactions.receipt', $transaction))
            ->assertOk()
            ->assertSee($transaction->receipt_number)
            ->assertSee('Setoran')
            ->assertSee('Ani Wijaya')
            ->assertSee('50.000')
            ->assertSee('75.000');
    }

    public function test_final_transaction_cannot_be_updated(): void
    {
        $transaction = $this->createDepositTransaction();

        $this->actingAsTeller()
            ->put("/teller/transactions/{$transaction->id}")
            ->assertStatus(404);
    }

    public function test_final_transaction_cannot_be_deleted(): void
    {
        $transaction = $this->createDepositTransaction();

        $this->actingAsTeller()
            ->delete("/teller/transactions/{$transaction->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_transaction_records_remain_after_receipt_view(): void
    {
        $transaction = $this->createDepositTransaction();

        $this->actingAsTeller()
            ->get(route('teller.transactions.receipt', $transaction));

        $this->assertSame(1, Transaction::count());
        $this->assertSame('success', $transaction->refresh()->status);
    }
}
