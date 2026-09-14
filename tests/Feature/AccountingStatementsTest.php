<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JurnalAkuntansi;
use App\Models\SystemConfig;
use App\Services\AccountingService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Teller\TellerTestCase;

class AccountingStatementsTest extends TellerTestCase
{
    protected function createZeroOpeningAccount(): Account
    {
        $customer = Customer::create([
            'student_number' => '2023777',
            'full_name' => 'Nasabah Baru',
            'class_name' => 'X RPL 9',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-zero-opening',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        return Account::create([
            'account_number' => '2026/2027-777',
            'customer_id' => $customer->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }

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

    public function test_balance_sheet_is_balanced_for_fully_journalized_account(): void
    {
        $account = $this->createZeroOpeningAccount();

        app(TransactionService::class)->deposit($account, [
            'slip_number' => 'SLIP-BS-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
        ], $this->teller);

        $sheet = app(AccountingService::class)->balanceSheet();

        $this->assertSame(100000, $sheet['total_assets']);
        $this->assertSame(100000, $sheet['total_liabilities']);
        $this->assertTrue($sheet['is_balanced']);
    }

    public function test_statements_accept_date_filter(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.income-statement', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Laporan Laba Rugi');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.balance-sheet', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Neraca');
    }

    public function test_statements_return_json(): void
    {
        $this->actingAs($this->admin, 'web')
            ->getJson(route('admin.reports.income-statement'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_income', 'net_income']]);

        $this->actingAs($this->admin, 'web')
            ->getJson(route('admin.reports.balance-sheet'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_assets', 'total_liabilities', 'is_balanced']]);
    }

    public function test_nasabah_cannot_open_balance_sheet(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('admin.reports.balance-sheet'))
            ->assertRedirect(route('login'));
    }
}
