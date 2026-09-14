<?php

namespace Tests\Feature;

use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class NewEndpointJsonTest extends TellerTestCase
{
    public function test_closing_store_returns_json(): void
    {
        $this->actingAsTeller()
            ->postJson(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 0,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_number', fn ($value) => is_string($value) && $value !== '');
    }

    public function test_reverse_returns_json(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-JSON-001',
            'slip_amount' => 20000,
            'physical_cash_amount' => 20000,
        ], $this->teller);

        $this->actingAs($this->supervisor, 'web')
            ->postJson(route('supervisor.transactions.reverse', $deposit), [
                'reason' => 'Koreksi via API.',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'reversal');
    }

    public function test_closing_store_rejects_mismatch_as_json(): void
    {
        app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-JSON-002',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        $this->actingAsTeller()
            ->postJson(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 40000,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
