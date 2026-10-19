<?php

namespace Tests\Feature\Admin;

use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DailyReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $teller;

    protected User $admin;

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

        $this->admin = User::create([
            'username' => 'admin1',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Admin Satu',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->report = DailyReport::create([
            'report_number' => 'RPT-20260802-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 100000,
            'total_withdrawal' => 50000,
            'closing_balance_system' => 50000,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function test_index_shows_reports(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.daily-reports.index'))
            ->assertOk()
            ->assertSee('Laporan harian Teller')
            ->assertSee($this->report->report_number);
    }

    public function test_index_filters_by_status(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.daily-reports.index', ['status' => 'approved']))
            ->assertOk()
            ->assertDontSee($this->report->report_number);
    }

    public function test_show_displays_report_details(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.daily-reports.show', $this->report))
            ->assertOk()
            ->assertSee($this->report->report_number)
            ->assertSee('Rp 50.000')
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 0');
    }

    public function test_approve_sets_status_and_creates_cash_transfer(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.daily-reports.approve', $this->report), [
                'approval_notes' => 'Laporan sesuai, kas diterima.',
            ])
            ->assertRedirect(route('admin.daily-reports.show', $this->report));

        $this->report->refresh();

        $this->assertSame('approved', $this->report->status);
        $this->assertSame($this->admin->id, $this->report->approved_by);
        $this->assertNotNull($this->report->approved_at);
        $this->assertSame('Laporan sesuai, kas diterima.', $this->report->approval_notes);

        $transfer = CashTransfer::first();
        $this->assertNotNull($transfer);
        $this->assertSame($this->report->id, $transfer->report_id);
        $this->assertSame($this->admin->id, $transfer->approved_by);
        $this->assertSame(50000, $transfer->amount);
        $this->assertSame('Laci Teller', $transfer->source_location);
        $this->assertSame('Brankas Utama', $transfer->destination_location);
        $this->assertNotNull($transfer->transferred_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'daily_report_approved',
            'entity_id' => $this->report->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'cash_transferred',
            'entity_id' => $this->report->id,
        ]);
    }

    public function test_admin_cannot_approve_own_report(): void
    {
        $this->report->update(['teller_id' => $this->admin->id]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.daily-reports.approve', $this->report), [
                'approval_notes' => 'OK',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->report->refresh();
        $this->assertSame('submitted', $this->report->status);
    }

    public function test_teller_cannot_approve_report(): void
    {
        $this->actingAs($this->teller, 'web')
            ->post(route('admin.daily-reports.approve', $this->report), [
                'approval_notes' => 'OK',
            ])
            ->assertRedirect(route('login'));

        $this->report->refresh();
        $this->assertSame('submitted', $this->report->status);
    }

    public function test_reject_sets_status(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.daily-reports.reject', $this->report), [
                'approval_notes' => 'Selisih ditemukan pada penghitungan.',
            ])
            ->assertRedirect(route('admin.daily-reports.show', $this->report));

        $this->report->refresh();

        $this->assertSame('rejected', $this->report->status);
        $this->assertNull($this->report->approved_by);
        $this->assertNull($this->report->approved_at);
        $this->assertSame('Selisih ditemukan pada penghitungan.', $this->report->approval_notes);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'daily_report_rejected',
            'entity_id' => $this->report->id,
        ]);
    }

    public function test_approve_requires_approval_notes(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.daily-reports.approve', $this->report), [])
            ->assertSessionHasErrors('approval_notes');
    }

    public function test_opening_balance_from_previous_approved_report(): void
    {
        DailyReport::create([
            'report_number' => 'RPT-20260801-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->subDay()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 200000,
            'total_withdrawal' => 50000,
            'closing_balance_system' => 150000,
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.daily-reports.show', $this->report))
            ->assertOk()
            ->assertSee('Rp 0');
    }

    public function test_cannot_approve_report_with_wrong_status(): void
    {
        $this->report->update(['status' => 'approved']);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.daily-reports.approve', $this->report), [
                'approval_notes' => 'OK',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
