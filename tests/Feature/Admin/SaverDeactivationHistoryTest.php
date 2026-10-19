<?php

namespace Tests\Feature\Admin;

use App\Models\DailyReport;
use App\Models\Transaction;
use Tests\Feature\Teller\TellerTestCase;

/**
 * Penabung tidak lagi bisa dihapus dari halaman Penabung; status aktif/diblokir
 * yang menggantikannya. Blokir harus benar-benar tidak merusak rekening, transaksi,
 * dan laporan yang sudah ada.
 */
class SaverDeactivationHistoryTest extends TellerTestCase
{
    public function test_transactions_of_deactivated_saver_remain_in_approved_report(): void
    {
        Transaction::create([
            'account_id' => $this->account->id,
            'teller_id' => $this->teller->id,
            'type' => 'deposit',
            'amount' => 100000,
            'balance_before' => 25000,
            'balance_after' => 125000,
            'receipt_number' => 'TRX-HIST-0001',
            'slip_number' => 'SLIP-HIST-001',
            'slip_amount' => 100000,
            'status' => 'success',
            'transaction_at' => now(),
        ]);

        $this->actingAsTeller()
            ->post(route('teller.closing.store'), [
                'report_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $report = DailyReport::firstOrFail();
        $this->assertSame('submitted', $report->status);

        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.savers.toggle-status', $this->nasabah))
            ->assertRedirect(route('admin.savers.index'));

        $this->assertSame('blocked', $this->nasabah->fresh()->status);
        $this->assertNotSoftDeleted('savers', ['id' => $this->nasabah->id]);
        $this->assertDatabaseHas('accounts', ['id' => $this->account->id, 'status' => 'active']);

        $this->actingAs($this->admin2, 'web')
            ->post(route('admin.daily-reports.approve', $report), ['approval_notes' => 'Kas sesuai dan lengkap'])
            ->assertRedirect();

        $this->assertSame('approved', $report->fresh()->status);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->account->id,
            'amount' => 100000,
        ]);

        $this->assertSame(1, $report->fresh()->transactions()->count());
    }
}
