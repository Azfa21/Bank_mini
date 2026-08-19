<?php

namespace Tests\Feature;

use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VaultJournalTest extends TestCase
{
    use RefreshDatabase;

    protected User $teller;

    protected User $supervisor;

    protected DailyReport $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teller = User::create([
            'username' => 'teller1',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Teller Satu',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $this->supervisor = User::create([
            'username' => 'supervisor1',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Supervisor Satu',
            'role' => 'supervisor',
            'status' => 'active',
        ]);

        $this->report = DailyReport::create([
            'report_number' => 'RPT-20260819-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 150000,
            'total_withdrawal' => 50000,
            'closing_balance_system' => 100000,
            'closing_balance_physical' => 100000,
            'cash_difference' => 0,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function test_approval_posts_vault_journal_102_debit_101_credit(): void
    {
        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.reports.approve', $this->report), [
                'approval_notes' => 'Kas diterima.',
            ])
            ->assertRedirect();

        $transfer = CashTransfer::firstOrFail();

        $this->assertSame(2, JurnalAkuntansi::where('cash_transfer_id', $transfer->id)->count());

        $this->assertDatabaseHas('jurnal_akuntansi', [
            'cash_transfer_id' => $transfer->id,
            'account_code' => '102',
            'entry_type' => 'debit',
            'debit_amount' => 100000,
            'credit_amount' => 0,
        ]);

        $this->assertDatabaseHas('jurnal_akuntansi', [
            'cash_transfer_id' => $transfer->id,
            'account_code' => '101',
            'entry_type' => 'credit',
            'debit_amount' => 0,
            'credit_amount' => 100000,
        ]);
    }

    public function test_vault_journal_is_balanced(): void
    {
        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.reports.approve', $this->report), [
                'approval_notes' => 'Kas diterima.',
            ]);

        $transfer = CashTransfer::firstOrFail();

        $totalDebit = (int) JurnalAkuntansi::where('cash_transfer_id', $transfer->id)->sum('debit_amount');
        $totalCredit = (int) JurnalAkuntansi::where('cash_transfer_id', $transfer->id)->sum('credit_amount');

        $this->assertSame($totalDebit, $totalCredit);
        $this->assertSame(100000, $totalDebit);
    }

    public function test_zero_amount_approval_does_not_post_journal(): void
    {
        $this->report->update([
            'closing_balance_system' => 0,
            'closing_balance_physical' => 0,
        ]);

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.reports.approve', $this->report), [
                'approval_notes' => 'Tidak ada kas.',
            ]);

        $this->assertSame(0, JurnalAkuntansi::count());
    }
}
