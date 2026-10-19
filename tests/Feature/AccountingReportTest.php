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
            ->assertSee('Neraca Saldo')
            ->assertSee('101')
            ->assertDontSee('value="201"', false)
            ->assertDontSee('Tabungan Nasabah');
    }

    public function test_general_ledger_ignores_hidden_account_code_from_url(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.general-ledger', ['account_code' => '201']))
            ->assertOk()
            ->assertDontSee('value="201" selected', false)
            ->assertDontSee('Tabungan Nasabah')
            ->assertSee('value="101" selected', false)
            ->assertSee('Mutasi Kas Teller (Laci Loket)');
    }

    public function test_admin_screens_do_not_render_savings_account(): void
    {
        $urls = [
            route('admin.dashboard'),
            route('admin.reports.general-ledger'),
            route('admin.journals.index'),
        ];

        foreach ($urls as $url) {
            $this->actingAs($this->admin, 'web')
                ->get($url)
                ->assertOk()
                ->assertDontSee('Tabungan Nasabah')
                ->assertDontSee('Simpanan Siswa')
                ->assertDontSee('value="201"', false);
        }
    }

    public function test_exports_do_not_include_savings_account(): void
    {
        $admin = fn () => $this->actingAs($this->admin, 'web');

        $this->assertStringNotContainsString(',201,', $admin()->get(route('admin.reports.export', 'journals'))->streamedContent());
        $this->assertStringNotContainsString('Tabungan', $admin()->get(route('admin.reports.export', 'trial_balance'))->streamedContent());
        $this->assertStringNotContainsString('Tabungan', $admin()->get(route('admin.reports.export', 'general_ledger').'?account_code=201')->streamedContent());
    }

    public function test_general_ledger_filters_by_account_code(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.general-ledger', ['account_code' => '101']))
            ->assertOk()
            ->assertSee('Kas Teller (Laci Loket)');
    }

    public function test_general_ledger_dropdown_marks_selected_account_code(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.general-ledger', ['account_code' => '102']))
            ->assertOk()
            ->assertSee('value="102" selected', false)
            ->assertDontSee('value="101" selected', false);
    }

    public function test_neraca_saldo_rows_link_to_mutation_detail(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.general-ledger', ['account_code' => '101']))
            ->assertOk()
            ->assertSee('account_code=102', false)
            ->assertSee('#mutasi', false)
            ->assertSee('Mutasi Kas Teller (Laci Loket)');
    }

    public function test_trial_balance_url_redirects_to_general_ledger(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.trial-balance'))
            ->assertRedirect(route('admin.reports.general-ledger'));
    }

    public function test_trial_balance_redirect_carries_date_filter(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.trial-balance', ['date' => '2026-10-01']))
            ->assertRedirect(route('admin.reports.general-ledger', ['date_to' => '2026-10-01']));
    }

    public function test_merged_page_shows_balanced_trial_balance(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.general-ledger'))
            ->assertOk()
            ->assertSee('Seimbang');
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

    public function test_export_general_ledger_respects_date_filter(): void
    {
        $streamed = fn (string $query) => $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.export', 'general_ledger').'?'.$query)
            ->streamedContent();

        $this->assertStringContainsString('Kas Teller', $streamed('account_code=101'));

        $tomorrow = now()->addDay()->toDateString();
        $this->assertStringNotContainsString('Kas Teller', $streamed('account_code=101&date_from='.$tomorrow));
    }

    public function test_export_trial_balance_csv(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.export', 'trial_balance'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Saldo Debit', $content);
        $this->assertStringContainsString('Kas Teller', $content);
        $this->assertStringContainsString('Seimbang', $content);
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
