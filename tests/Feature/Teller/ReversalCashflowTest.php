<?php

namespace Tests\Feature\Teller;

use App\Models\DailyReport;
use App\Models\Transaction;
use App\Services\DailyReportService;
use App\Services\ReversalService;
use App\Services\TransactionService;

class ReversalCashflowTest extends TellerTestCase
{
    public function test_same_day_deposit_reversal_keeps_closing_cash_balanced(): void
    {
        $service = app(TransactionService::class);

        $deposit = $service->deposit($this->account, [
            'slip_number' => 'SLIP-CF-001',
            'slip_amount' => 100000,
        ], $this->teller);

        app(ReversalService::class)->reverse($deposit, $this->admin2, 'Setoran dibatalkan hari yang sama.');

        $recap = $this->actingAsTeller()
            ->get(route('teller.closing.index'))
            ->assertOk()
            ->viewData('recap');

        $this->assertSame(100000, $recap['total_deposit']);
        $this->assertSame(100000, $recap['total_withdrawal']);
        $this->assertSame(0, $recap['closing_balance_system']);

        $this->actingAsTeller()
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $report = DailyReport::firstOrFail();
        $this->assertSame(0, $report->closing_balance_system);
    }

    public function test_same_day_withdrawal_reversal_keeps_closing_cash_balanced(): void
    {
        $service = app(TransactionService::class);

        $withdrawal = $service->withdraw($this->account, [
            'amount' => 10000,
            'slip_number' => 'SLIP-CF-002',
        ], '123456', $this->teller);

        app(ReversalService::class)->reverse($withdrawal, $this->admin2, 'Penarikan dibatalkan.');

        $recap = $this->actingAsTeller()
            ->get(route('teller.closing.index'))
            ->assertOk()
            ->viewData('recap');

        $this->assertSame(10000, $recap['total_deposit']);
        $this->assertSame(10000, $recap['total_withdrawal']);
        $this->assertSame(0, $recap['closing_balance_system']);
    }

    public function test_reversal_of_previous_day_deposit_removes_carried_cash(): void
    {
        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => 100000,
            'balance_before' => 25000,
            'balance_after' => 125000,
            'receipt_number' => 'TRX-CF-YDAY-001',
            'status' => 'success',
            'transaction_at' => now()->subDay(),
        ]);

        $this->account->update(['balance' => 125000]);

        $deposit = Transaction::where('receipt_number', 'TRX-CF-YDAY-001')->firstOrFail();

        app(ReversalService::class)->reverse($deposit, $this->admin2, 'Koreksi setoran hari sebelumnya.');

        $this->assertSame(25000, $this->account->refresh()->balance);

        $recap = $this->actingAsTeller()
            ->get(route('teller.closing.index'))
            ->assertOk()
            ->viewData('recap');

        $this->assertSame(100000, $recap['opening_balance']);
        $this->assertSame(100000, $recap['total_withdrawal']);
        $this->assertSame(0, $recap['closing_balance_system']);
    }

    public function test_cashflow_counts_reversal_once(): void
    {
        $service = app(TransactionService::class);

        $deposit = $service->deposit($this->account, [
            'slip_number' => 'SLIP-CF-004',
            'slip_amount' => 40000,
        ], $this->teller);

        app(ReversalService::class)->reverse($deposit, $this->admin2, 'Koreksi tunggal.');

        $recap = app(DailyReportService::class)->recap($this->teller, now()->toDateString());

        $this->assertSame(40000, $recap['total_deposit']);
        $this->assertSame(40000, $recap['total_withdrawal']);
    }
}
