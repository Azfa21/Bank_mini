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

    public function test_closing_ignores_breakdown_mismatch_when_physical_matches(): void
    {
        $this->seedDeposit(100000);

        $this->actingAsTeller()
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 100000,
                'denominations' => [
                    'kertas_50000' => 1,
                ],
            ])
            ->assertRedirect();

        $report = DailyReport::firstOrFail();

        // Total fisik ketikan Teller adalah sumber kebenaran; rincian hanya bukti audit.
        $this->assertSame(100000, $report->closing_balance_physical);
        $this->assertSame(['kertas_50000' => 1], $report->closing_breakdown);
        $this->assertSame(0, $report->cash_difference);
    }

    public function test_closing_allows_one_rupiah_difference_with_note(): void
    {
        $this->seedDeposit(100000);

        $this->actingAsTeller()
            ->from(route('teller.closing.index'))
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                // Uang Rp1 tidak ada fisiknya: Teller mengetik total asli + berita acara.
                'physical_cash' => 99999,
                'difference_note' => 'Kurang Rp1 setelah hitung ulang dua kali.',
            ])
            ->assertRedirect();

        $report = DailyReport::firstOrFail();

        $this->assertSame(99999, $report->closing_balance_physical);
        $this->assertSame(-1, $report->cash_difference);
        $this->assertStringContainsString('Kurang Rp1', (string) $report->approval_notes);
    }

    public function test_closing_rejects_difference_without_note(): void
    {
        $this->seedDeposit(100000);

        $this->actingAsTeller()
            ->from(route('teller.closing.index'))
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 99999,
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
            ->assertSee('Rincian kertas dan logam')
            ->assertSee('Rp 100.000');
    }
}
