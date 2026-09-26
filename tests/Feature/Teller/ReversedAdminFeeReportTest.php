<?php

namespace Tests\Feature\Teller;

use App\Models\JurnalAkuntansi;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Services\DailyReportService;
use App\Services\ReversalService;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;

class ReversedAdminFeeReportTest extends TellerTestCase
{
    public function test_reversed_withdrawal_has_zero_net_admin_fee_in_daily_and_monthly_reports(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'admin_fee'], ['config_value' => '2000']);

        $withdrawal = app(TransactionService::class)->withdraw(
            $this->account,
            ['slip_number' => 'SLIP-001', 'amount' => 10000],
            '123456',
            $this->teller
        );

        app(ReversalService::class)->reverse($withdrawal, $this->supervisor, 'Reverse complete withdrawal');

        $recap = app(DailyReportService::class)->recap($this->teller, now()->toDateString());
        $report = app(DailyReportService::class)->createReport($this->teller, now()->toDateString(), 0);

        $adminIncomeCredits = (int) JurnalAkuntansi::where('account_code', TransactionService::ACCOUNT_CODE_ADMIN_INCOME)
            ->sum('credit_amount');
        $adminIncomeDebits = (int) JurnalAkuntansi::where('account_code', TransactionService::ACCOUNT_CODE_ADMIN_INCOME)
            ->sum('debit_amount');

        $this->assertSame(0, $recap['total_admin_fee']);
        $this->assertSame(0, $report->total_admin_fee);
        $this->assertSame(0, $adminIncomeCredits - $adminIncomeDebits);

        $monthly = $this->actingAs($this->admin, 'web')
            ->getJson(route('admin.reports.monthly', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->json('data');

        $this->assertSame(0, $monthly['totals']['admin_fee']);
        $this->assertSame(0, $monthly['perTeller'][0]['admin_fee']);
    }

    public function test_previous_day_reversal_creates_negative_net_fee_on_reversal_day(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'admin_fee'], ['config_value' => '2000']);

        $withdrawal = Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'withdrawal',
            'amount' => 10000,
            'admin_fee' => 2000,
            'balance_before' => 25000,
            'balance_after' => 13000,
            'receipt_number' => 'TRX-001',
            'slip_number' => 'SLIP-001',
            'status' => 'success',
            'transaction_at' => now()->subDay(),
        ]);

        $this->account->update(['balance' => 13000]);

        app(ReversalService::class)->reverse($withdrawal, $this->supervisor, 'Reverse previous-day withdrawal');

        $recap = app(DailyReportService::class)->recap($this->teller, now()->toDateString());
        $report = app(DailyReportService::class)->createReport($this->teller, now()->toDateString(), 0);

        $this->assertSame(-2000, $recap['total_admin_fee']);
        $this->assertSame(-2000, $report->total_admin_fee);
    }

    public function test_reversal_fee_is_offset_in_the_reversal_month(): void
    {
        Carbon::setTestNow('2026-10-01 09:00:00');

        $withdrawal = Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'withdrawal',
            'amount' => 10000,
            'admin_fee' => 2000,
            'balance_before' => 25000,
            'balance_after' => 13000,
            'receipt_number' => 'TRX-001',
            'slip_number' => 'SLIP-001',
            'status' => 'success',
            'transaction_at' => '2026-09-30 10:00:00',
        ]);

        $this->account->update(['balance' => 13000]);

        app(ReversalService::class)->reverse($withdrawal, $this->supervisor, 'Reverse previous-month withdrawal');

        $september = $this->actingAs($this->admin, 'web')
            ->getJson(route('admin.reports.monthly', ['month' => '2026-09']))
            ->assertOk()
            ->json('data');
        $october = $this->actingAs($this->admin, 'web')
            ->getJson(route('admin.reports.monthly', ['month' => '2026-10']))
            ->assertOk()
            ->json('data');

        $this->assertSame(2000, $september['totals']['admin_fee']);
        $this->assertSame(-2000, $october['totals']['admin_fee']);
    }
}
