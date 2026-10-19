<?php

namespace Tests\Feature\Teller;

use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\Transaction;
use App\Services\DailyReportService;

class CashSweepIntegrityTest extends TellerTestCase
{
    public function test_recap_opening_equals_unclosed_net_activity(): void
    {
        $yesterday = now()->subDay()->toDateString();

        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => 100000,
            'balance_before' => 25000,
            'balance_after' => 125000,
            'receipt_number' => 'TRX-SWEEP-0001',
            'status' => 'success',
            'transaction_at' => now()->subDay(),
        ]);

        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'withdrawal',
            'amount' => 20000,
            'balance_before' => 125000,
            'balance_after' => 105000,
            'receipt_number' => 'TRX-SWEEP-0002',
            'status' => 'success',
            'transaction_at' => now()->subDay(),
        ]);

        DailyReport::create([
            'report_number' => 'RPT-SWEEP-0001',
            'teller_id' => $this->teller->id,
            'report_date' => $yesterday,
            'opening_balance' => 0,
            'total_deposit' => 100000,
            'total_withdrawal' => 20000,
            'closing_balance_system' => 60000,
            'status' => 'approved',
            'approved_by' => 1,
            'approved_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
        ]);

        $recap = app(DailyReportService::class)->recap($this->teller, now()->toDateString());

        $this->assertSame(20000, $recap['opening_balance']);
    }

    public function test_approval_rejects_sweep_exceeding_journaled_cash(): void
    {
        JurnalAkuntansi::create([
            'transaction_id' => null,
            'cash_transfer_id' => null,
            'journal_number' => 'JRN-SWEEP-01',
            'account_code' => '101',
            'entry_type' => 'debit',
            'amount' => 30000,
            'debit_amount' => 30000,
            'credit_amount' => 0,
            'description' => 'Setoran tunai',
            'status' => 'posted',
            'journal_at' => now(),
        ]);

        $report = DailyReport::create([
            'report_number' => 'RPT-SWEEP-0002',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 98000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 98000,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->admin2, 'web')
            ->post(route('admin.daily-reports.approve', $report), [
                'approval_notes' => 'OK',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, CashTransfer::count());

        $report->refresh();
        $this->assertSame('submitted', $report->status);
    }

    public function test_buku_besar_warns_negative_ending_balance(): void
    {
        JurnalAkuntansi::create([
            'transaction_id' => null,
            'cash_transfer_id' => null,
            'journal_number' => 'JRN-SWEEP-02',
            'account_code' => '101',
            'entry_type' => 'credit',
            'amount' => 50000,
            'debit_amount' => 0,
            'credit_amount' => 50000,
            'description' => 'Pemindahan kas ke brankas',
            'status' => 'posted',
            'journal_at' => now(),
        ]);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.general-ledger', ['account_code' => '101']))
            ->assertOk()
            ->assertSee('saldo akhir akun ini negatif')
            ->assertSee('text-red-600');
    }
}
