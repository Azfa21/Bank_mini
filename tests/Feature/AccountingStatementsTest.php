<?php

namespace Tests\Feature;

use App\Models\JurnalAkuntansi;
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

        // Beban operasional 501 sebesar 500 (jurnal seimbang dengan kas).
        JurnalAkuntansi::create([
            'transaction_id' => null,
            'cash_transfer_id' => null,
            'journal_number' => 'JRN-EXP-001',
            'account_code' => AccountingService::ACCOUNT_CODE_OPERATING_EXPENSE,
            'entry_type' => 'debit',
            'amount' => 500,
            'debit_amount' => 500,
            'credit_amount' => 0,
            'description' => 'Beban operasional',
            'status' => 'posted',
            'journal_at' => now(),
        ]);
        JurnalAkuntansi::create([
            'transaction_id' => null,
            'cash_transfer_id' => null,
            'journal_number' => 'JRN-EXP-002',
            'account_code' => AccountingService::ACCOUNT_CODE_CASH_TELLER,
            'entry_type' => 'credit',
            'amount' => 500,
            'debit_amount' => 0,
            'credit_amount' => 500,
            'description' => 'Beban operasional',
            'status' => 'posted',
            'journal_at' => now(),
        ]);

        $statement = app(AccountingService::class)->incomeStatement();

        $this->assertSame(2000, $statement['total_income']);
        $this->assertSame(500, $statement['total_expense']);
        $this->assertSame(1500, $statement['net_income']);
    }

    public function test_income_statement_page_shows_expense_account(): void
    {
        JurnalAkuntansi::create([
            'transaction_id' => null,
            'cash_transfer_id' => null,
            'journal_number' => 'JRN-EXP-010',
            'account_code' => AccountingService::ACCOUNT_CODE_OPERATING_EXPENSE,
            'entry_type' => 'debit',
            'amount' => 1000,
            'debit_amount' => 1000,
            'credit_amount' => 0,
            'description' => 'Beban operasional',
            'status' => 'posted',
            'journal_at' => now(),
        ]);
        JurnalAkuntansi::create([
            'transaction_id' => null,
            'cash_transfer_id' => null,
            'journal_number' => 'JRN-EXP-011',
            'account_code' => AccountingService::ACCOUNT_CODE_CASH_TELLER,
            'entry_type' => 'credit',
            'amount' => 1000,
            'debit_amount' => 0,
            'credit_amount' => 1000,
            'description' => 'Beban operasional',
            'status' => 'posted',
            'journal_at' => now(),
        ]);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.income-statement'))
            ->assertOk()
            ->assertSee('501')
            ->assertSee('Beban Operasional Bank Mini')
            ->assertSee('Rp 1.000');
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
