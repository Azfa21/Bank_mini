<?php

namespace Tests\Feature;

use App\Models\Transaction;
use Tests\Feature\Teller\TellerTestCase;

class MonthlyReportTest extends TellerTestCase
{
    private function seedTransaction(): void
    {
        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => 100000,
            'balance_before' => 25000,
            'balance_after' => 125000,
            'receipt_number' => 'TRX-MONTH-0001',
            'slip_number' => 'SLIP-MONTH-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'status' => 'success',
            'transaction_at' => now(),
        ]);
    }

    public function test_admin_can_open_monthly_report(): void
    {
        $this->seedTransaction();

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.monthly', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Laporan bulanan')
            ->assertSee('Rp 100.000');
    }

    public function test_supervisor_can_open_monthly_report(): void
    {
        $this->seedTransaction();

        $this->actingAs($this->supervisor, 'web')
            ->get(route('supervisor.reports.monthly', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Laporan bulanan');
    }

    public function test_monthly_report_print_page_loads(): void
    {
        $this->seedTransaction();

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.reports.monthly.print', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Laporan rekapitulasi bulanan');
    }

    public function test_nasabah_cannot_open_monthly_report(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('admin.reports.monthly'))
            ->assertRedirect(route('login'));
    }

    public function test_teller_cannot_open_monthly_report(): void
    {
        $this->actingAsTeller()
            ->get(route('admin.reports.monthly'))
            ->assertRedirect(route('login'));
    }
}
