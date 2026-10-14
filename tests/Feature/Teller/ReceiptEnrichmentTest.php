<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Saver;
use App\Services\ReversalService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;

class ReceiptEnrichmentTest extends TellerTestCase
{
    protected function createRecipient(): Account
    {
        $customer = Saver::create([
            'student_number' => '2023988',
            'full_name' => 'Rudi Penerima',
            'class_name' => 'X RPL 5',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-receipt-recipient',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        return Account::create([
            'account_number' => '2026/2027-988',
            'saver_id' => $customer->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }

    public function test_deposit_receipt_shows_bank_reference_terbilang_and_status(): void
    {
        $transaction = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-RCP-001',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        $journalNumber = $transaction->journalEntries()->first()->journal_number;

        $this->actingAsTeller()
            ->get(route('teller.transactions.receipt', $transaction))
            ->assertOk()
            ->assertSee('Bank Mini Sekolah')
            ->assertSee($journalNumber)
            ->assertSee('Lima Puluh Ribu Rupiah')
            ->assertSee('success')
            ->assertSee('Ani Wijaya')
            ->assertSee('2023001');
    }

    public function test_transfer_receipt_shows_recipient_details(): void
    {
        $recipient = $this->createRecipient();

        $transaction = app(TransactionService::class)->transfer(
            $this->account,
            $recipient,
            ['amount' => 10000, 'slip_number' => 'SLIP-RCP-003'],
            '123456',
            $this->teller,
        );

        $this->actingAsTeller()
            ->get(route('teller.transactions.receipt', $transaction))
            ->assertOk()
            ->assertSee('Transfer Antar Rekening')
            ->assertSee('Rekening tujuan')
            ->assertSee('2026/2027-988')
            ->assertSee('Rudi Penerima');
    }

    public function test_reversal_receipt_marks_pembalik_and_original_number(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-RCP-004',
            'slip_amount' => 30000,
            'physical_cash_amount' => 30000,
        ], $this->teller);

        $reversal = app(ReversalService::class)->reverse($deposit, $this->supervisor, 'Salah input.');

        $this->actingAsTeller()
            ->get(route('teller.transactions.receipt', $reversal))
            ->assertOk()
            ->assertSee('JURNAL PEMBALIK')
            ->assertSee('Koreksi (Jurnal Pembalik)')
            ->assertSee('Koreksi dari')
            ->assertSee($deposit->receipt_number);
    }

    public function test_original_receipt_shows_corrected_banner_after_reversal(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-RCP-005',
            'slip_amount' => 30000,
            'physical_cash_amount' => 30000,
        ], $this->teller);

        app(ReversalService::class)->reverse($deposit, $this->supervisor, 'Salah input nominal.');

        $this->actingAsTeller()
            ->get(route('teller.transactions.receipt', $deposit->fresh()))
            ->assertOk()
            ->assertSee('dikoreksi')
            ->assertSee('Salah input nominal.');
    }

    public function test_receipt_rejects_other_teller_transaction(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-RCP-006',
            'slip_amount' => 10000,
            'physical_cash_amount' => 10000,
        ], $this->supervisor);

        $this->actingAsTeller()
            ->get(route('teller.transactions.receipt', $deposit))
            ->assertForbidden();
    }
}
