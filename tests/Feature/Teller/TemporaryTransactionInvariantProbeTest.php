<?php

namespace Tests\Feature\Teller;

use App\Exceptions\TransactionValidationException;
use App\Models\Transaction;
use App\Services\DailyReportService;
use App\Services\ReversalService;
use App\Services\TransactionService;
use App\Support\CashDenomination;

class TemporaryTransactionInvariantProbeTest extends TellerTestCase
{
    public function test_deposit_denomination_total_may_disagree_with_cash_and_slip(): void
    {
        $this->actingAsTeller()
            ->postJson(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-001',
                'slip_amount' => 50000,
                'physical_cash_amount' => 50000,
                'denomination_breakdown' => ['kertas_100000' => 1],
            ])
            ->assertCreated();

        $transaction = Transaction::where('type', 'deposit')->firstOrFail();

        $this->assertSame(50000, $transaction->amount);
        $this->assertSame(100000, CashDenomination::total($transaction->denomination_breakdown));
        $this->assertSame(['kertas_100000' => 1], $transaction->denomination_breakdown);
    }

    public function test_withdrawal_denomination_total_may_disagree_with_amount(): void
    {
        $this->actingAsTeller()
            ->postJson(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-001',
                'amount' => 10000,
                'pin' => '123456',
                'denomination_breakdown' => ['kertas_100000' => 1],
            ])
            ->assertCreated();

        $transaction = Transaction::where('type', 'withdrawal')->firstOrFail();

        $this->assertSame(15000, $transaction->balance_after);
        $this->assertSame(['kertas_100000' => 1], $transaction->denomination_breakdown);
    }

    public function test_report_can_be_created_without_matching_physical_breakdown(): void
    {
        app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-001',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        $report = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString(),
            50000,
            ['kertas_100000' => 1]
        );

        $this->assertSame(50000, $report->closing_balance_physical);
        $this->assertSame(0, $report->cash_difference);
        $this->assertSame(['kertas_100000' => 1], $report->closing_breakdown);
    }

    public function test_report_is_frozen_at_submission_while_reversal_changes_related_state(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-001',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        $report = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString(),
            50000
        );

        app(ReversalService::class)->reverse($deposit, $this->supervisor, 'Correction after submission');

        $freshRecap = app(DailyReportService::class)->recap($this->teller, now()->toDateString());

        $this->assertSame(50000, $report->closing_balance_system);
        $this->assertSame(0, $freshRecap['closing_balance_system']);
        $this->assertSame('submitted', $report->fresh()->status);
    }

    public function test_reversal_after_report_approval_is_rejected(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-001',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        $report = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString(),
            50000
        );

        app(DailyReportService::class)->approve($report, $this->supervisor, 'Approved');

        $this->expectException(TransactionValidationException::class);
        app(ReversalService::class)->reverse($deposit, $this->supervisor, 'Correction after approval');
    }

    public function test_withdrawal_after_report_submission_is_rejected(): void
    {
        app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString(),
            0
        );

        $this->expectException(TransactionValidationException::class);
        app(TransactionService::class)->withdraw(
            $this->account,
            ['slip_number' => 'SLIP-002', 'amount' => 10000],
            '123456',
            $this->teller
        );
    }

    public function test_unsigned_integer_overflow_rolls_back_without_balance_corruption(): void
    {
        $balanceBefore = $this->account->balance;

        try {
            app(TransactionService::class)->deposit($this->account, [
                'slip_number' => 'SLIP-001',
                'slip_amount' => PHP_INT_MAX,
                'physical_cash_amount' => PHP_INT_MAX,
            ], $this->teller);
        } catch (\Throwable) {
            // Expected: the transaction must fail because balance_after is unsigned bigint.
        }

        $this->assertSame($balanceBefore, $this->account->refresh()->balance);
        $this->assertSame(0, Transaction::count());
    }
}
