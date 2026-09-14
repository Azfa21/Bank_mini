<?php

namespace Tests\Feature;

use App\Models\SystemConfig;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class ReportStatementTest extends TellerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SystemConfig::updateOrCreate(['config_key' => 'admin_fee'], ['config_value' => '2000']);

        $service = app(TransactionService::class);

        $service->deposit($this->account, [
            'slip_number' => 'SLIP-RPT-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'amount' => 100000,
        ], $this->teller);

        $service->withdraw($this->account, [
            'amount' => 10000,
            'slip_number' => 'SLIP-RPT-002',
        ], '123456', $this->teller);
    }

    public function test_admin_can_open_income_statement(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.income-statement'))
            ->assertOk()
            ->assertSee('Laporan Laba Rugi')
            ->assertSee('401')
            ->assertSee('Rp 2.000');
    }

    public function test_admin_can_open_balance_sheet(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.balance-sheet'))
            ->assertOk()
            ->assertSee('Neraca')
            ->assertSee('Total Aset');
    }

    public function test_supervisor_can_open_both_statements(): void
    {
        $this->actingAs($this->supervisor, 'web')
            ->get(route('supervisor.reports.income-statement'))
            ->assertOk()
            ->assertSee('Laporan Laba Rugi');

        $this->actingAs($this->supervisor, 'web')
            ->get(route('supervisor.reports.balance-sheet'))
            ->assertOk()
            ->assertSee('Neraca');
    }

    public function test_nasabah_cannot_open_statements(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('admin.reports.income-statement'))
            ->assertRedirect(route('login'));
    }
}
