<?php

namespace Tests\Feature\Nasabah;

use App\Models\Account;
use App\Models\Saver;
use App\Services\ReversalService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;

class ReversalDisplayTest extends NasabahTestCase
{
    public function test_mutasi_shows_deposit_reversal_as_debit(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-DISP-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
        ], $this->teller);

        app(ReversalService::class)->reverse($deposit, $this->supervisor, 'Koreksi setoran.');

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi'))
            ->assertOk()
            ->assertSee('Koreksi')
            ->assertSee('- Rp 100.000', false);
    }

    public function test_mutasi_shows_withdrawal_reversal_as_credit(): void
    {
        $withdrawal = app(TransactionService::class)->withdraw($this->account, [
            'amount' => 10000,
            'slip_number' => 'SLIP-DISP-002',
        ], '123456', $this->teller);

        app(ReversalService::class)->reverse($withdrawal, $this->supervisor, 'Koreksi penarikan.');

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi'))
            ->assertOk()
            ->assertSee('Koreksi')
            ->assertSee('+ Rp 10.000', false);
    }

    public function test_statement_renders_correction_label(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-DISP-003',
            'slip_amount' => 25000,
            'physical_cash_amount' => 25000,
        ], $this->teller);

        app(ReversalService::class)->reverse($deposit, $this->supervisor, 'Koreksi setoran.');

        $this->actingAsNasabah()
            ->get(route('nasabah.statement'))
            ->assertOk()
            ->assertSee('Koreksi transaksi');
    }

    public function test_recipient_sees_transfer_reversal_as_debit(): void
    {
        $recipient = Saver::create([
            'student_number' => '2023900',
            'full_name' => 'Dewi Penerima',
            'class_name' => 'X RPL 4',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-recipient-1',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $recipientAccount = Account::create([
            'account_number' => '2026/2027-900',
            'saver_id' => $recipient->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);

        $transfer = app(TransactionService::class)->transfer(
            $this->account,
            $recipientAccount,
            ['amount' => 10000, 'slip_number' => 'SLIP-DISP-004'],
            '123456',
            $this->teller,
        );

        app(ReversalService::class)->reverse($transfer, $this->supervisor, 'Transfer dibatalkan.');

        $this->actingAs($recipient, 'nasabah')
            ->get(route('nasabah.mutasi'))
            ->assertOk()
            ->assertSee('Koreksi')
            ->assertSee('- Rp 10.000', false);
    }
}
