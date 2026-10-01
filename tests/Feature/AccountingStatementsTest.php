<?php

namespace Tests\Feature;

use App\Models\SystemConfig;
use App\Services\AccountingService;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class AccountingStatementsTest extends TellerTestCase
{
    public function test_income_statement_calculates_net_income(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'admin_fee'], ['config_value' => '2000']);

        $service = app(TransactionService::class);
        $service->deposit($this->account, [
            'slip_number' => 'SLIP-IS-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
        ], $this->teller);
        $service->withdraw($this->account, ['amount' => 10000], '123456', $this->teller);

        $statement = app(AccountingService::class)->incomeStatement();

        $this->assertSame(2000, $statement['total_income']);
        $this->assertSame(2000, $statement['net_income']);
        $this->assertArrayNotHasKey('total_expense', $statement);
    }

    public function test_income_statement_page_shows_income_account(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'admin_fee'], ['config_value' => '2000']);

        app(TransactionService::class)
            ->withdraw($this->account, ['amount' => 10000], '123456', $this->teller);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.income-statement'))
            ->assertOk()
            ->assertSee('401')
            ->assertSee('Pendapatan Administrasi')
            ->assertSee('Rp 2.000');
    }

    public function test_statements_accept_date_filter(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.income-statement', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Laporan Laba Rugi');
    }

    public function test_statements_return_json(): void
    {
        $this->actingAs($this->admin, 'web')
            ->getJson(route('admin.reports.income-statement'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_income', 'net_income']]);
    }
}
