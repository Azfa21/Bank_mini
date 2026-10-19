<?php

namespace Tests\Feature\Teller;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\DailyReport;
use App\Models\Saver;
use App\Models\Transaction;
use App\Services\DailyReportService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;

class PostSubmissionTransactionGuardTest extends TellerTestCase
{
    public function test_transaction_before_report_submission_remains_allowed(): void
    {
        $transaction = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'PRE-SUBMISSION',
            'slip_amount' => 50000,
        ], $this->teller);

        $report = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString()
        );

        $this->assertSame('success', $transaction->status);
        $this->assertSame('submitted', $report->status);
        $this->assertSame(50000, $report->closing_balance_system);
    }

    public function test_deposit_withdrawal_and_transfer_are_blocked_after_report_submission(): void
    {
        $destination = $this->createDestinationAccount();
        $report = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString()
        );
        $startingBalance = $this->account->balance;

        $attempts = [
            fn () => app(TransactionService::class)->deposit($this->account, [
                'slip_number' => 'POST-DEPOSIT',
                'slip_amount' => 10000,
            ], $this->teller),
            fn () => app(TransactionService::class)->withdraw(
                $this->account,
                ['slip_number' => 'POST-WITHDRAWAL', 'amount' => 10000],
                '123456',
                $this->teller,
            ),
            fn () => app(TransactionService::class)->transfer(
                $this->account,
                $destination,
                [
                    'slip_number' => 'POST-TRANSFER',
                    'recipient_account_number' => $destination->account_number,
                    'amount' => 10000,
                ],
                '123456',
                $this->teller,
            ),
        ];

        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('A submitted report must block new transactions.');
            } catch (TransactionValidationException $exception) {
                $this->assertStringContainsString('sudah disubmitkan', $exception->getMessage());
            }
        }

        $this->assertSame($startingBalance, $this->account->refresh()->balance);
        $this->assertSame(0, $destination->refresh()->balance);
        $this->assertSame(0, Transaction::count());
        $this->assertSame('submitted', $report->fresh()->status);
    }

    public function test_rejected_report_allows_activity_and_resubmission(): void
    {
        $report = DailyReport::create([
            'report_number' => 'REJECTED-001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 0,
            'total_withdrawal' => 0,
            'closing_balance_system' => 0,
            'status' => 'rejected',
            'submitted_at' => now(),
        ]);

        app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'AFTER-REJECTION',
            'slip_amount' => 50000,
        ], $this->teller);

        $resubmitted = app(DailyReportService::class)->createReport(
            $this->teller,
            now()->toDateString()
        );

        $this->assertSame($report->id, $resubmitted->id);
        $this->assertSame('submitted', $resubmitted->status);
        $this->assertSame(50000, $resubmitted->total_deposit);
    }

    public function test_submitted_report_for_another_date_does_not_block_current_activity(): void
    {
        app(DailyReportService::class)->createReport(
            $this->teller,
            now()->subDay()->toDateString()
        );

        $transaction = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'NEW-DATE',
            'slip_amount' => 10000,
        ], $this->teller);

        $this->assertSame('success', $transaction->status);
        $this->assertSame(35000, $this->account->refresh()->balance);
    }

    private function createDestinationAccount(): Account
    {
        $saver = Saver::create([
            'student_number' => '2023009',
            'full_name' => 'Destination Saver',
            'class_name' => 'X RPL 1',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-destination',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        return Account::create([
            'account_number' => '2026/2027-009',
            'saver_id' => $saver->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }
}
