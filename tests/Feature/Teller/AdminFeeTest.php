<?php

namespace Tests\Feature\Teller;

use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Services\TransactionService;

class AdminFeeTest extends TellerTestCase
{
    public function test_withdrawal_applies_admin_fee_and_income_journal(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'admin_fee'], ['config_value' => '2000']);

        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-FEE-001',
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertRedirect();

        $transaction = Transaction::where('type', 'withdrawal')->firstOrFail();

        $this->assertSame(2000, $transaction->admin_fee);
        $this->assertSame(25000, $transaction->balance_before);
        $this->assertSame(13000, $transaction->balance_after);
        $this->assertSame(13000, $this->account->refresh()->balance);

        $this->assertSame(3, $transaction->journalEntries()->count());
        $this->assertSame(12000, (int) JurnalAkuntansi::where('transaction_id', $transaction->id)->sum('debit_amount'));
        $this->assertSame(12000, (int) JurnalAkuntansi::where('transaction_id', $transaction->id)->sum('credit_amount'));

        $this->assertDatabaseHas('jurnal_akuntansi', [
            'transaction_id' => $transaction->id,
            'account_code' => '401',
            'credit_amount' => 2000,
        ]);
    }

    public function test_withdrawal_rejected_when_fee_pushes_balance_below_minimum(): void
    {
        $this->account->update(['balance' => 15000]);
        SystemConfig::updateOrCreate(['config_key' => 'admin_fee'], ['config_value' => '6000']);

        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-FEE-002',
                'amount' => 1000,
                'pin' => '123456',
            ])
            ->assertRedirect(route('teller.withdrawals.create', $this->account))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(15000, $this->account->refresh()->balance);
    }

    public function test_withdrawal_rejected_above_max_without_order(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'max_withdrawal_without_order'], ['config_value' => '10000']);

        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-FEE-003',
                'amount' => 15000,
                'pin' => '123456',
            ])
            ->assertRedirect(route('teller.withdrawals.create', $this->account))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());
    }

    public function test_withdrawal_allowed_when_amount_equals_limit(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'max_withdrawal_without_order'], ['config_value' => '10000']);

        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-FEE-004',
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertRedirect();

        $transaction = Transaction::where('type', 'withdrawal')->firstOrFail();
        $this->assertSame(10000, $transaction->amount);
        $this->assertSame(15000, $this->account->refresh()->balance);
    }

    public function test_closing_recap_and_print_include_admin_fee(): void
    {
        SystemConfig::updateOrCreate(['config_key' => 'admin_fee'], ['config_value' => '2000']);

        $service = app(TransactionService::class);
        $service->deposit($this->account, [
            'slip_number' => 'SLIP-FEE-005',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
        ], $this->teller);
        $service->withdraw($this->account, ['amount' => 10000], '123456', $this->teller);

        $recap = $this->actingAsTeller()
            ->get(route('teller.closing.index'))
            ->assertOk()
            ->viewData('recap');

        $this->assertSame(2000, $recap['total_admin_fee']);
        $this->assertSame(90000, $recap['closing_balance_system']);

        $this->actingAsTeller()
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 90000,
            ])
            ->assertRedirect();

        $report = DailyReport::firstOrFail();
        $this->assertSame(2000, $report->total_admin_fee);

        $this->actingAsTeller()
            ->get(route('teller.closing.print', $report))
            ->assertOk()
            ->assertSee('Biaya Administrasi')
            ->assertSee('Rp 2.000');
    }
}
