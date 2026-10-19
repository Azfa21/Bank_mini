<?php

namespace Tests\Feature\Teller;

use App\Services\ReversalService;
use App\Services\TransactionService;

class PassbookReversalTest extends TellerTestCase
{
    public function test_passbook_lists_deposit_correction_row(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-PBR-001',
            'slip_amount' => 50000,
        ], $this->teller);

        app(ReversalService::class)->reverse($deposit, $this->admin2, 'Koreksi setoran.');

        $this->actingAsTeller()
            ->get(route('teller.passbook.show', $this->account))
            ->assertOk()
            ->assertSee('Koreksi');
    }

    public function test_passbook_print_uses_type_label_for_correction(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-PBR-002',
            'slip_amount' => 30000,
        ], $this->teller);

        app(ReversalService::class)->reverse($deposit, $this->admin2, 'Koreksi setoran.');

        $this->actingAsTeller()
            ->get(route('teller.passbook.print', ['account' => $this->account, 'start_line' => 1]))
            ->assertOk()
            ->assertSee('Koreksi');
    }
}
