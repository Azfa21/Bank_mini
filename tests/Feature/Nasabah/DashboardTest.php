<?php

namespace Tests\Feature\Nasabah;

use App\Models\Saver;
use App\Models\Transaction;
use App\Services\TransactionService;

class DashboardTest extends NasabahTestCase
{
    public function test_nasabah_dashboard_shows_own_balance_and_account_number(): void
    {
        $this->actingAsNasabah()
            ->get(route('nasabah.dashboard'))
            ->assertOk()
            ->assertSee('Rp 25.000')
            ->assertSee('2026/2027-001')
            ->assertSee('Ani Wijaya');
    }

    public function test_nasabah_dashboard_shows_last_transaction(): void
    {
        $this->deposit(50000);

        $this->actingAsNasabah()
            ->get(route('nasabah.dashboard'))
            ->assertOk()
            ->assertSee('Setoran')
            ->assertSee('Rp 50.000');
    }

    public function test_nasabah_dashboard_links_to_mutasi(): void
    {
        $this->actingAsNasabah()
            ->get(route('nasabah.dashboard'))
            ->assertOk()
            ->assertSee('Riwayat Mutasi')
            ->assertSee(route('nasabah.mutasi'));
    }

    public function test_nasabah_without_account_sees_notice(): void
    {
        Saver::create([
            'student_number' => '2023998',
            'full_name' => 'Tanpa Rekening',
            'class_name' => 'X RPL 2',
            'phone_number' => '081234567998',
            'pin_hash' => bcrypt('123456'),
            'qr_code_value' => 'qr-no-account',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->actingAs(Saver::where('student_number', '2023998')->first(), 'nasabah')
            ->get(route('nasabah.dashboard'))
            ->assertOk()
            ->assertSee('belum punya rekening aktif');
    }

    public function test_nasabah_cannot_access_admin_journal_audit(): void
    {
        $this->actingAsNasabah()
            ->get(route('admin.journals.index'))
            ->assertRedirect(route('login'));
    }

    public function test_nasabah_cannot_access_teller_transaction_page(): void
    {
        $this->actingAsNasabah()
            ->get(route('teller.deposits.create', $this->account))
            ->assertRedirect(route('login'));
    }

    protected function deposit(int $amount): Transaction
    {
        return app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-N-001',
            'slip_amount' => $amount,
            'physical_cash_amount' => $amount,
            'amount' => $amount,
        ], $this->teller);
    }
}
