<?php

namespace Tests\Feature;

use App\Models\CashRequest;
use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\User;
use App\Services\CashRequestService;
use App\Services\DailyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CashRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $teller;

    protected User $admin;

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
    }

    protected function makeRequest(int $amount = 300000): CashRequest
    {
        $request = CashRequest::create([
            'teller_id' => $this->teller->id,
            'amount' => $amount,
            'request_number' => 'REQ-TEST-0001',
            'status' => 'pending',
            'created_at' => now(),
        ]);

        return $request->refresh();
    }

    protected function makeDailyReport(string $status = 'submitted'): DailyReport
    {
        return DailyReport::create([
            'report_number' => 'RPT-TEST-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 0,
            'total_withdrawal' => 0,
            'closing_balance_system' => 0,
            'status' => $status,
            'submitted_at' => $status === 'submitted' ? now() : null,
        ]);
    }

    public function test_teller_can_submit_cash_request(): void
    {
        $response = $this->actingAs($this->teller, 'web')
            ->post(route('teller.cash-requests.store'), [
                'amount' => 300000,
            ])
            ->assertRedirect();

        $request = CashRequest::firstOrFail();

        $this->assertSame(300000, $request->amount);
        $this->assertSame('pending', $request->status);
        $this->assertSame($this->teller->id, $request->teller_id);
        $this->assertStringStartsWith('REQ-', $request->request_number);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'cash_request_created',
            'entity_id' => $request->id,
        ]);

        $response->assertRedirect(route('teller.cash-requests.index'));
    }

    public function test_submission_validates_input(): void
    {
        $this->actingAs($this->teller, 'web')
            ->post(route('teller.cash-requests.store'), [
                'amount' => 0,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, CashRequest::count());
    }

    public function test_admin_index_lists_pending_requests_and_cash_positions(): void
    {
        $cashRequest = $this->makeRequest();

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.cash-requests.index'))
            ->assertOk()
            ->assertSee($cashRequest->request_number)
            ->assertSee('Teller Satu');
    }

    public function test_approval_posts_journal_debit_101_credit_102(): void
    {
        $cashRequest = $this->makeRequest();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.cash-requests.approve', $cashRequest), [
                'decision_notes' => 'Kas utama cukup, kas diserahkan langsung.',
            ])
            ->assertRedirect(route('admin.cash-requests.index'));

        $cashRequest->refresh();

        $this->assertSame('approved', $cashRequest->status);
        $this->assertSame($this->admin->id, $cashRequest->approved_by);
        $this->assertNotNull($cashRequest->approved_at);
        $this->assertSame('Kas utama cukup, kas diserahkan langsung.', $cashRequest->decision_notes);

        $this->assertSame(2, JurnalAkuntansi::where('cash_request_id', $cashRequest->id)->count());

        $this->assertDatabaseHas('jurnal_akuntansi', [
            'cash_request_id' => $cashRequest->id,
            'account_code' => '101',
            'entry_type' => 'debit',
            'debit_amount' => 300000,
            'credit_amount' => 0,
        ]);

        $this->assertDatabaseHas('jurnal_akuntansi', [
            'cash_request_id' => $cashRequest->id,
            'account_code' => '102',
            'entry_type' => 'credit',
            'debit_amount' => 0,
            'credit_amount' => 300000,
        ]);

        $this->assertSame(300000, (int) JurnalAkuntansi::where('cash_request_id', $cashRequest->id)->sum('debit_amount'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'cash_request_approved',
            'entity_id' => $cashRequest->id,
        ]);
    }

    public function test_approval_does_not_require_decision_notes(): void
    {
        $cashRequest = $this->makeRequest();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.cash-requests.approve', $cashRequest), [])
            ->assertRedirect(route('admin.cash-requests.index'));

        $cashRequest->refresh();

        $this->assertSame('approved', $cashRequest->status);
        $this->assertNull($cashRequest->decision_notes);
        $this->assertSame(2, JurnalAkuntansi::where('cash_request_id', $cashRequest->id)->count());
    }

    public function test_rejection_requires_notes(): void
    {
        $cashRequest = $this->makeRequest();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.cash-requests.reject', $cashRequest), [])
            ->assertSessionHasErrors('decision_notes');

        $cashRequest->refresh();
        $this->assertSame('pending', $cashRequest->status);
    }

    public function test_admin_page_only_asks_for_notes_when_rejecting(): void
    {
        $this->makeRequest();

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.cash-requests.index'))
            ->assertOk()
            ->assertSee('Setujui')
            ->assertDontSee('Setujui dan kirim kas')
            ->assertDontSee('Catatan keputusan wajib')
            ->assertSee('Alasan penolakan wajib');
    }

    public function test_approve_blocked_when_already_decided(): void
    {
        $cashRequest = $this->makeRequest();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.cash-requests.approve', $cashRequest), [
                'decision_notes' => 'Setuju.',
            ]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.cash-requests.approve', $cashRequest), [
                'decision_notes' => 'Setuju dua kali.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, CashRequest::where('status', 'approved')->count());
    }

    public function test_reject_records_reason_without_journal(): void
    {
        $cashRequest = $this->makeRequest();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.cash-requests.reject', $cashRequest), [
                'decision_notes' => 'Kas utama belum mencukupi hari ini.',
            ])
            ->assertRedirect(route('admin.cash-requests.index'));

        $cashRequest->refresh();

        $this->assertSame('rejected', $cashRequest->status);
        $this->assertSame('Kas utama belum mencukupi hari ini.', $cashRequest->decision_notes);
        $this->assertSame(0, JurnalAkuntansi::where('cash_request_id', $cashRequest->id)->count());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'cash_request_rejected',
            'entity_id' => $cashRequest->id,
        ]);
    }

    public function test_teller_cannot_access_admin_decisions(): void
    {
        $cashRequest = $this->makeRequest();

        $this->actingAs($this->teller, 'web')
            ->post(route('admin.cash-requests.approve', $cashRequest), [
                'decision_notes' => 'Setuju.',
            ])
            ->assertRedirect(route('login'));

        $cashRequest->refresh();
        $this->assertSame('pending', $cashRequest->status);
    }

    public function test_multiple_pending_requests_allowed(): void
    {
        // Uang laci bisa tetap kurang setelah permintaan sebelumnya disetujui,
        // jadi teller boleh mengajukan lagi tanpa menunggu keputusan sebelumnya.
        $this->actingAs($this->teller, 'web')
            ->post(route('teller.cash-requests.store'), [
                'amount' => 200000,
            ]);

        $this->actingAs($this->teller, 'web')
            ->post(route('teller.cash-requests.store'), [
                'amount' => 150000,
            ])
            ->assertRedirect();

        $this->assertSame(2, CashRequest::where('status', 'pending')->count());
    }

    public function test_teller_cannot_submit_after_closing_cash(): void
    {
        $this->makeDailyReport();

        $this->actingAs($this->teller, 'web')
            ->from(route('teller.cash-requests.index'))
            ->post(route('teller.cash-requests.store'), [
                'amount' => 300000,
            ])
            ->assertRedirect(route('teller.cash-requests.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, CashRequest::count());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'cash_request_creation_blocked',
            'user_id' => $this->teller->id,
        ]);
    }

    public function test_teller_cannot_submit_after_daily_report_is_approved(): void
    {
        $this->makeDailyReport('approved');

        $this->actingAs($this->teller, 'web')
            ->postJson(route('teller.cash-requests.store'), [
                'amount' => 300000,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame(0, CashRequest::count());
    }

    public function test_teller_can_submit_again_when_closing_report_is_rejected(): void
    {
        $this->makeDailyReport('rejected');

        $this->actingAs($this->teller, 'web')
            ->post(route('teller.cash-requests.store'), [
                'amount' => 300000,
            ])
            ->assertRedirect(route('teller.cash-requests.index'));

        $this->assertSame(1, CashRequest::where('status', 'pending')->count());
    }

    public function test_teller_page_hides_request_form_after_closing_cash(): void
    {
        $report = $this->makeDailyReport();

        $this->actingAs($this->teller, 'web')
            ->get(route('teller.cash-requests.index'))
            ->assertOk()
            ->assertSee('Penutupan kas sudah dibuat')
            ->assertSee($report->report_number)
            ->assertDontSee('Kirim permintaan');
    }

    public function test_admin_cannot_approve_request_after_teller_closes_cash(): void
    {
        $cashRequest = $this->makeRequest();
        $this->makeDailyReport();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.cash-requests.approve', $cashRequest), [
                'decision_notes' => 'Kas telat diserahkan.',
            ])
            ->assertSessionHas('error');

        $cashRequest->refresh();

        $this->assertSame('pending', $cashRequest->status);
        $this->assertSame(0, JurnalAkuntansi::where('cash_request_id', $cashRequest->id)->count());
    }

    public function test_recap_counts_approved_cash_request(): void
    {
        $service = app(CashRequestService::class);

        $cashRequest = $service->submit($this->teller, 400000);
        $service->approve($cashRequest, $this->admin, 'Setuju.');

        $recap = app(DailyReportService::class)->recap($this->teller, now()->toDateString());

        $this->assertSame(400000, $recap['total_cash_received']);
        $this->assertSame(400000, $recap['closing_balance_system']);
        $this->assertSame(0, $recap['opening_balance']);
    }

    public function test_recap_ignores_pending_requests(): void
    {
        $service = app(CashRequestService::class);

        $service->submit($this->teller, 400000);

        $recap = app(DailyReportService::class)->recap($this->teller, now()->toDateString());

        $this->assertSame(0, $recap['total_cash_received']);
        $this->assertSame(0, $recap['closing_balance_system']);
    }

    public function test_daily_report_sweep_includes_received_cash(): void
    {
        $service = app(CashRequestService::class);
        $cashRequest = $service->submit($this->teller, 400000);
        $service->approve($cashRequest, $this->admin, 'Setuju.');

        $report = app(DailyReportService::class)->createReport($this->teller, now()->toDateString());

        $this->assertSame(400000, (int) $report->total_cash_received);
        $this->assertSame(400000, (int) $report->closing_balance_system);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.daily-reports.approve', $report), [
                'approval_notes' => 'Kas laci diserahkan lengkap.',
            ])
            ->assertRedirect();

        // Kas yang laporan harian menyapu laci mencakup kas modal dari kas utama.
        $transfer = CashTransfer::firstOrFail();
        $this->assertSame(400000, (int) $transfer->amount);
    }

    public function test_teller_page_lists_own_requests(): void
    {
        $cashRequest = $this->makeRequest();

        $this->actingAs($this->teller, 'web')
            ->get(route('teller.cash-requests.index'))
            ->assertOk()
            ->assertSee($cashRequest->request_number)
            ->assertSee('Menunggu');
    }
}
