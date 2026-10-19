<?php

namespace Tests\Feature\Teller;

use App\Exceptions\TransactionValidationException;
use App\Models\Transaction;
use App\Services\DailyReportService;
use App\Services\ReversalService;
use App\Services\TransactionService;

class TemporaryTransactionInvariantProbeTest extends TellerTestCase
{
    public function test_report_is_frozen_at_submission_while_reversal_changes_related_state(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-001',
            'slip_amount' => 50000,
        ], $this->teller);

        $report = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString()
        );

        app(ReversalService::class)->reverse($deposit, $this->admin2, 'Correction after submission');

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
        ], $this->teller);

        $report = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString()
        );

        app(DailyReportService::class)->approve($report, $this->admin2, 'Approved');

        $this->expectException(TransactionValidationException::class);
        app(ReversalService::class)->reverse($deposit, $this->admin2, 'Correction after approval');
    }

    public function test_withdrawal_after_report_submission_is_rejected(): void
    {
        app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString()
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
            ], $this->teller);
        } catch (\Throwable) {
            // Expected: the transaction must fail because balance_after is unsigned bigint.
        }

        $this->assertSame($balanceBefore, $this->account->refresh()->balance);
        $this->assertSame(0, Transaction::count());
    }
}
