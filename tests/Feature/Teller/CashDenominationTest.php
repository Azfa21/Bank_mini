<?php

namespace Tests\Feature\Teller;

use App\Models\DailyReport;
use App\Models\Transaction;

class CashDenominationTest extends TellerTestCase
{
    protected function seedDeposit(int $amount = 100000): void
    {
        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => 25000,
            'balance_after' => 25000 + $amount,
            'receipt_number' => 'TRX-DEN-'.uniqid(),
            'status' => 'success',
            'transaction_at' => now(),
        ]);
    }

    public function test_closing_with_denominations_computes_physical_cash(): void
    {
        $this->seedDeposit(100000);

        $this->actingAsTeller()
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 100000,
                'denominations' => [
                    'kertas_100000' => 1,
                    'kertas_50000' => 0,
                ],
            ])
            ->assertRedirect();

        $report = DailyReport::firstOrFail();

        $this->assertSame(100000, $report->closing_balance_physical);
        $this->assertSame(['kertas_100000' => 1], $report->closing_breakdown);
        $this->assertSame(0, $report->cash_difference);
    }

    public function test_closing_rejects_when_denomination_total_mismatch(): void
    {
        $this->seedDeposit(100000);

        $this->actingAsTeller()
            ->from(route('teller.closing.index'))
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 100000,
                'denominations' => [
                    'kertas_50000' => 1,
                ],
            ])
            ->assertRedirect(route('teller.closing.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, DailyReport::count());
    }

    public function test_report_print_shows_denomination_breakdown(): void
    {
        $report = DailyReport::create([
            'report_number' => 'RPT-DEN-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 100000,
            'total_withdrawal' => 0,
            'total_admin_fee' => 0,
            'closing_balance_system' => 100000,
            'closing_balance_physical' => 100000,
            'closing_breakdown' => ['kertas_100000' => 1],
            'cash_difference' => 0,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAsTeller()
            ->get(route('teller.closing.print', $report))
            ->assertOk()
            ->assertSee('Rincian Uang Kertas')
            ->assertSee('Rp 100.000');
    }
}
