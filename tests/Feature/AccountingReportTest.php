<?php

namespace Tests\Feature;

use App\Services\AnalyticsService;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class AccountingReportTest extends TellerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $service = app(TransactionService::class);

        $service->deposit($this->account, [
            'slip_number' => 'SLIP-ACC-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'amount' => 100000,
        ], $this->teller);

        $service->withdraw($this->account, [
            'amount' => 25000,
            'slip_number' => 'SLIP-ACC-002',
        ], '123456', $this->teller);
    }

    public function test_admin_can_open_general_ledger(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.general-ledger'))
            ->assertOk()
            ->assertSee('Buku Besar')
            ->assertSee('101')
            ->assertSee('201');
    }

    public function test_general_ledger_filters_by_account_code(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.general-ledger', ['account_code' => '101']))
            ->assertOk()
            ->assertSee('101 — Kas Teller');
    }

    public function test_general_ledger_dropdown_marks_selected_account_code(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.general-ledger', ['account_code' => '102']))
            ->assertOk()
            ->assertSee('value="102" selected', false)
            ->assertDontSee('value="101" selected', false);
    }

    public function test_admin_can_open_trial_balance(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.trial-balance'))
            ->assertOk()
            ->assertSee('Neraca Saldo')
            ->assertSee('Seimbang');
    }

    public function test_trial_balance_debit_equals_credit(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.trial-balance'))
            ->assertOk()
            ->assertSee('Seimbang');
    }

    public function test_supervisor_can_open_trial_balance(): void
    {
        $this->actingAs($this->supervisor, 'web')
            ->get(route('supervisor.reports.trial-balance'))
            ->assertOk()
            ->assertSee('Neraca Saldo');
    }

    public function test_export_csv_returns_streamed_file(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.export', 'journals'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
    }

    public function test_export_general_ledger_csv(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.export', 'general_ledger').'?account_code=101');

        $response->assertOk();
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
    }

    public function test_analytics_service_balances_are_consistent(): void
    {
        $summary = app(AnalyticsService::class)->summary();

        $this->assertSame(75000, $summary['total_savings']);
        $this->assertSame(75000, $summary['total_cash']);
        $this->assertSame(100.0, $summary['liquidity_ratio_percent']);
    }

    public function test_nasabah_cannot_access_accounting_reports(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('admin.reports.trial-balance'))
            ->assertRedirect(route('login'));
    }
}
