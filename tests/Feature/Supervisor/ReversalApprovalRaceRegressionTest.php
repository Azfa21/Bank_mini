<?php

namespace Tests\Feature\Supervisor;

use App\Exceptions\TransactionValidationException;
use App\Models\CashTransfer;
use App\Services\DailyReportService;
use App\Services\ReversalService;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class ReversalApprovalRaceRegressionTest extends TellerTestCase
{
    public function test_submitted_report_cannot_be_approved_after_related_transaction_is_reversed(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'RACE-REGRESSION-1',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        $report = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString(),
            50000,
        );

        app(ReversalService::class)->reverse(
            $deposit,
            $this->supervisor,
            'Correction before approval.',
        );

        try {
            app(DailyReportService::class)->approve(
                $report,
                $this->supervisor,
                'Must not approve a stale report.',
            );
            $this->fail('A stale daily report was approved after its related transaction was reversed.');
        } catch (TransactionValidationException $exception) {
            $this->assertSame('submitted', $report->fresh()->status);
            $this->assertNull(CashTransfer::where('report_id', $report->id)->value('amount'));
            $this->assertSame(25000, $this->account->fresh()->balance);
        }
    }

    public function test_reversal_remains_blocked_when_report_was_approved_first(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'RACE-REGRESSION-2',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        $report = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString(),
            50000,
        );

        app(DailyReportService::class)->approve($report, $this->supervisor, 'Approved first.');

        try {
            app(ReversalService::class)->reverse(
                $deposit,
                $this->supervisor,
                'Must not reverse after approval.',
            );
            $this->fail('A transaction was reversed after its daily report was approved.');
        } catch (TransactionValidationException $exception) {
            $this->assertSame('success', $deposit->fresh()->status);
            $this->assertSame(75000, $this->account->fresh()->balance);
        }
    }
}