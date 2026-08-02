<?php

namespace Tests\Feature\Teller;

use App\Models\DailyReport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ClosingTest extends TellerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_closing_page_loads_with_recap(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.closing.index'))
            ->assertOk()
            ->assertSee('Penutupan Kas')
            ->assertSee('Saldo Akhir Sistem');
    }

    public function test_recap_shows_correct_totals(): void
    {
        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => 100000,
            'balance_before' => 25000,
            'balance_after' => 125000,
            'receipt_number' => 'TRX-20260802-0001',
            'slip_number' => 'SLIP-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'status' => 'success',
            'transaction_at' => now(),
        ]);

        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'withdrawal',
            'amount' => 50000,
            'balance_before' => 125000,
            'balance_after' => 75000,
            'receipt_number' => 'TRX-20260802-0002',
            'slip_number' => 'SLIP-002',
            'slip_amount' => 50000,
            'status' => 'success',
            'transaction_at' => now(),
        ]);

        $this->actingAsTeller()
            ->get(route('teller.closing.index'))
            ->assertOk()
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 50.000')
            ->assertSee('Rp 50.000');
    }

    public function test_create_report_when_cash_matches(): void
    {
        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => 100000,
            'balance_before' => 25000,
            'balance_after' => 125000,
            'receipt_number' => 'TRX-20260802-0003',
            'slip_number' => 'SLIP-003',
            'slip_amount' => 100000,
            'status' => 'success',
            'transaction_at' => now(),
        ]);

        $this->actingAsTeller()
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 100000,
            ])
            ->assertRedirect();

        $report = DailyReport::first();

        $this->assertNotNull($report);
        $this->assertSame('submitted', $report->status);
        $this->assertSame(0, $report->opening_balance);
        $this->assertSame(100000, $report->total_deposit);
        $this->assertSame(0, $report->total_withdrawal);
        $this->assertSame(100000, $report->closing_balance_system);
        $this->assertSame(100000, $report->closing_balance_physical);
        $this->assertSame(0, $report->cash_difference);
        $this->assertNotNull($report->report_number);
        $this->assertNotNull($report->submitted_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'daily_report_created',
            'entity_name' => 'daily_report',
            'entity_id' => $report->id,
        ]);
    }

    public function test_create_report_rejects_when_cash_differs(): void
    {
        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => 100000,
            'balance_before' => 25000,
            'balance_after' => 125000,
            'receipt_number' => 'TRX-20260802-0004',
            'slip_number' => 'SLIP-004',
            'slip_amount' => 100000,
            'status' => 'success',
            'transaction_at' => now(),
        ]);

        $this->actingAsTeller()
            ->from(route('teller.closing.index'))
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 95000,
            ])
            ->assertRedirect(route('teller.closing.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, DailyReport::count());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'daily_report_creation_blocked',
        ]);
    }

    public function test_create_report_rejects_negative_physical_cash(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => -1000,
            ])
            ->assertSessionHasErrors('physical_cash');

        $this->assertSame(0, DailyReport::count());
    }

    public function test_cannot_create_duplicate_report_for_same_date(): void
    {
        DailyReport::create([
            'report_number' => 'RPT-20260802-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 100000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 100000,
            'closing_balance_physical' => 100000,
            'cash_difference' => 0,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAsTeller()
            ->from(route('teller.closing.index'))
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 100000,
            ])
            ->assertRedirect(route('teller.closing.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, DailyReport::count());
    }

    public function test_rejected_report_can_be_resubmitted(): void
    {
        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => 100000,
            'balance_before' => 25000,
            'balance_after' => 125000,
            'receipt_number' => 'TRX-20260802-0005',
            'slip_number' => 'SLIP-005',
            'slip_amount' => 100000,
            'status' => 'success',
            'transaction_at' => now(),
        ]);

        DailyReport::create([
            'report_number' => 'RPT-20260802-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 100000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 100000,
            'closing_balance_physical' => 95000,
            'cash_difference' => -5000,
            'status' => 'rejected',
        ]);

        $this->actingAsTeller()
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 100000,
            ])
            ->assertRedirect();

        $report = DailyReport::first();

        $this->assertSame('submitted', $report->status);
        $this->assertSame(100000, $report->closing_balance_physical);
        $this->assertSame(0, $report->cash_difference);
        $this->assertSame(1, DailyReport::count());
    }

    public function test_report_number_is_generated(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
                'physical_cash' => 0,
            ])
            ->assertRedirect();

        $report = DailyReport::first();

        $this->assertMatchesRegularExpression('/^RPT-\d{8}-\d{4}$/', $report->report_number);
    }

    public function test_admin_cannot_access_closing(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('teller.closing.index'))
            ->assertRedirect(route('login'));
    }

    public function test_print_page_loads(): void
    {
        $report = DailyReport::create([
            'report_number' => 'RPT-20260802-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 100000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 100000,
            'closing_balance_physical' => 100000,
            'cash_difference' => 0,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAsTeller()
            ->get(route('teller.closing.print', $report))
            ->assertOk()
            ->assertSee('Laporan Harian Teller')
            ->assertSee($report->report_number);
    }

    public function test_print_page_rejects_other_teller(): void
    {
        $otherTeller = User::create([
            'username' => 'teller2',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Teller Dua',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $report = DailyReport::create([
            'report_number' => 'RPT-20260802-0001',
            'teller_id' => $otherTeller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 100000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 100000,
            'closing_balance_physical' => 100000,
            'cash_difference' => 0,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAsTeller()
            ->get(route('teller.closing.print', $report))
            ->assertForbidden();
    }
}
