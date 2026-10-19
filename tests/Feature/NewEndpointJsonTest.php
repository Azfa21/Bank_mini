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
        ], $this->teller);

        $this->actingAs($this->admin2, 'web')
            ->postJson(route('admin.transactions.reverse', $deposit), [
                'reason' => 'Koreksi via API.',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'reversal');
    }
}
