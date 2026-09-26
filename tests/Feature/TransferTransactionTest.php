<?php

namespace Tests\Feature;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\JurnalAkuntansi;
use App\Models\Saver;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Teller\TellerTestCase;

class TransferTransactionTest extends TellerTestCase
{
    protected Account $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $recipient = Saver::create([
            'student_number' => '2023002',
            'full_name' => 'Budi Santoso',
            'class_name' => 'X RPL 1',
            'phone_number' => '081234567002',
            'pin_hash' => Hash::make('654321'),
            'qr_code_value' => 'qr-test-2',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->destination = Account::create([
            'account_number' => '2026/2027-002',
            'saver_id' => $recipient->id,
            'balance' => 5000,
            'opening_balance' => 5000,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }

    private function service(): TransactionService
    {
        return app(TransactionService::class);
    }

    public function test_transfer_moves_balance_between_accounts(): void
    {
        $transaction = $this->service()->transfer(
            $this->account,
            $this->destination,
            ['amount' => 10000, 'notes' => 'Bayar iuran kelas'],
            '123456',
            $this->teller
        );

        $this->assertSame('transfer', $transaction->type);
        $this->assertSame($this->destination->id, $transaction->recipient_account_id);
        $this->assertSame(10000, $transaction->amount);
        $this->assertSame(25000, $transaction->balance_before);
        $this->assertSame(15000, $transaction->balance_after);

        $this->assertSame(15000, $this->account->refresh()->balance);
        $this->assertSame(15000, $this->destination->refresh()->balance);
    }

    public function test_transfer_creates_double_entry_journal_201_debit_credit(): void
    {
        $transaction = $this->service()->transfer(
            $this->account,
            $this->destination,
            ['amount' => 10000],
            '123456',
            $this->teller
        );

        $this->assertSame(2, JurnalAkuntansi::where('transaction_id', $transaction->id)->count());

        $this->assertDatabaseHas('jurnal_akuntansi', [
            'transaction_id' => $transaction->id,
            'account_code' => '201',
            'entry_type' => 'debit',
            'debit_amount' => 10000,
        ]);

        $this->assertDatabaseHas('jurnal_akuntansi', [
            'transaction_id' => $transaction->id,
            'account_code' => '201',
            'entry_type' => 'credit',
            'credit_amount' => 10000,
        ]);
    }

    public function test_transfer_rejects_wrong_pin(): void
    {
        $this->expectException(TransactionValidationException::class);

        $this->service()->transfer(
            $this->account,
            $this->destination,
            ['amount' => 10000],
            '000000',
            $this->teller
        );
    }

    public function test_transfer_rejects_insufficient_balance(): void
    {
        $this->expectException(TransactionValidationException::class);
        $this->expectExceptionMessage('Saldo rekening pengirim tidak mencukupi.');

        $this->service()->transfer(
            $this->account,
            $this->destination,
            ['amount' => 1000000],
            '123456',
            $this->teller
        );
    }

    public function test_transfer_rejects_below_minimum_balance(): void
    {
        $this->expectException(TransactionValidationException::class);

        $this->service()->transfer(
            $this->account,
            $this->destination,
            ['amount' => 20000],
            '123456',
            $this->teller
        );
    }

    public function test_transfer_rejects_same_account(): void
    {
        $this->expectException(TransactionValidationException::class);
        $this->expectExceptionMessage('Rekening tujuan tidak boleh sama dengan rekening pengirim.');

        $this->service()->transfer(
            $this->account,
            $this->account,
            ['amount' => 10000],
            '123456',
            $this->teller
        );
    }

    public function test_transfer_to_inactive_account_is_rejected(): void
    {
        $this->destination->update(['status' => 'closed']);

        $this->expectException(TransactionValidationException::class);

        $this->service()->transfer(
            $this->account,
            $this->destination,
            ['amount' => 10000],
            '123456',
            $this->teller
        );
    }

    public function test_teller_http_endpoint_processes_transfer(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.transfers.create', $this->account))
            ->post(route('teller.transfers.store', $this->account), [
                'recipient_account_number' => $this->destination->account_number,
                'amount' => 5000,
                'pin' => '123456',
                'notes' => 'Transfer via loket',
            ])
            ->assertRedirect(route('teller.transactions.receipt', Transaction::where('type', 'transfer')->firstOrFail()));

        $this->assertSame(20000, $this->account->refresh()->balance);
        $this->assertSame(10000, $this->destination->refresh()->balance);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_transfer_success',
        ]);
    }
}
