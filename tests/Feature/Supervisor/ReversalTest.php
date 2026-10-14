<?php

namespace Tests\Feature\Supervisor;

use App\Models\JurnalAkuntansi;
use App\Models\Transaction;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class ReversalTest extends TellerTestCase
{
    public function test_supervisor_reverses_deposit_restores_balance_and_posts_reversing_journal(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-REV-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'amount' => 100000,
        ], $this->teller);

        $this->assertSame(125000, $this->account->refresh()->balance);

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.transactions.reverse', $deposit), [
                'reason' => 'Salah input nominal setoran.',
            ])
            ->assertRedirect();

        $deposit->refresh();
        $this->assertSame('reversed', $deposit->status);
        $this->assertNotNull($deposit->reversed_at);
        $this->assertSame($this->supervisor->id, $deposit->reversed_by);
        $this->assertSame(25000, $this->account->refresh()->balance);

        $reversal = Transaction::where('type', 'reversal')->firstOrFail();
        $this->assertSame($deposit->id, $reversal->reversal_of_transaction_id);
        $this->assertSame(100000, $reversal->amount);

        $this->assertSame(2, $reversal->journalEntries()->count());
        $this->assertSame(100000, (int) JurnalAkuntansi::where('transaction_id', $reversal->id)->sum('debit_amount'));
        $this->assertSame(100000, (int) JurnalAkuntansi::where('transaction_id', $reversal->id)->sum('credit_amount'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'transaction_reversed']);
    }

    public function test_supervisor_reverses_withdrawal_and_restores_balance(): void
    {
        $withdrawal = app(TransactionService::class)->withdraw($this->account, [
            'slip_number' => 'SLIP-REV-002',
            'amount' => 10000,
        ], '123456', $this->teller);

        $this->assertSame(15000, $this->account->refresh()->balance);

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.transactions.reverse', $withdrawal), [
                'reason' => 'Pembatalan penarikan tunai.',
            ])
            ->assertRedirect();

        $this->assertSame(25000, $this->account->refresh()->balance);

        $reversal = Transaction::where('type', 'reversal')->firstOrFail();
        $this->assertSame(2, $reversal->journalEntries()->count());
    }

    public function test_teller_cannot_reverse_transaction(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-REV-003',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
            'amount' => 50000,
        ], $this->teller);

        $this->actingAsTeller()
            ->post(route('supervisor.transactions.reverse', $deposit), [
                'reason' => 'Coba koreksi.',
            ])
            ->assertRedirect(route('login'));

        $this->assertSame('success', $deposit->refresh()->status);
        $this->assertSame(75000, $this->account->refresh()->balance);
    }

    public function test_cannot_reverse_already_reversed_transaction(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-REV-004',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
            'amount' => 50000,
        ], $this->teller);

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.transactions.reverse', $deposit), ['reason' => 'Koreksi pertama.'])
            ->assertRedirect();

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.transactions.reverse', $deposit), ['reason' => 'Koreksi kedua.'])
            ->assertForbidden();

        $this->assertSame(1, Transaction::where('type', 'reversal')->count());
    }

    public function test_reverse_requires_reason(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-REV-005',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
            'amount' => 50000,
        ], $this->teller);

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.transactions.reverse', $deposit), ['reason' => 'x'])
            ->assertSessionHasErrors('reason');
    }
}
