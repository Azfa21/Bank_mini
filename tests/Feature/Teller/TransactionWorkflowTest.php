<?php

namespace Tests\Feature\Teller;

use App\Exceptions\TransactionValidationException;
use App\Models\Account;
use App\Models\Saver;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;

class TransactionWorkflowTest extends TellerTestCase
{
    protected Account $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $recipient = Saver::create([
            'student_number' => '2023999',
            'full_name' => 'Desti Nasabah',
            'class_name' => 'X RPL 2',
            'phone_number' => '081299999999',
            'pin_hash' => Hash::make('654321'),
            'qr_code_value' => 'qr-workflow-dest',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->destination = Account::create([
            'account_number' => '2026/2027-999',
            'saver_id' => $recipient->id,
            'balance' => 5000,
            'opening_balance' => 5000,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }

    public function test_withdrawal_leaving_balance_exactly_at_minimum_succeeds(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-WF-001',
                'amount' => 15000,
                'pin' => '123456',
            ])
            ->assertRedirect();

        $transaction = Transaction::where('type', 'withdrawal')->firstOrFail();

        $this->assertSame(25000, $transaction->balance_before);
        $this->assertSame(10000, $transaction->balance_after);
        $this->assertSame(10000, $this->account->refresh()->balance);
    }

    public function test_withdrawal_one_rupiah_past_minimum_boundary_fails(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-WF-002',
                'amount' => 15001,
                'pin' => '123456',
            ])
            ->assertRedirect(route('teller.withdrawals.create', $this->account))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_transfer_leaving_source_exactly_at_minimum_succeeds(): void
    {
        app(TransactionService::class)->transfer(
            $this->account,
            $this->destination,
            ['amount' => 15000],
            '123456',
            $this->teller
        );

        $this->assertSame(10000, $this->account->refresh()->balance);
        $this->assertSame(20000, $this->destination->refresh()->balance);
    }

    public function test_transfer_one_rupiah_past_minimum_boundary_fails(): void
    {
        $this->expectException(TransactionValidationException::class);
        $this->expectExceptionMessage('tidak boleh kurang dari');

        try {
            app(TransactionService::class)->transfer(
                $this->account,
                $this->destination,
                ['amount' => 15001],
                '123456',
                $this->teller
            );
        } finally {
            $this->assertSame(25000, $this->account->refresh()->balance);
            $this->assertSame(5000, $this->destination->refresh()->balance);
        }
    }

    public function test_transfer_generates_default_notes_when_absent(): void
    {
        $transaction = app(TransactionService::class)->transfer(
            $this->account,
            $this->destination,
            ['amount' => 5000],
            '123456',
            $this->teller
        );

        $this->assertSame('Transfer ke Rek. '.$this->destination->account_number, $transaction->notes);
    }

    public function test_sequential_transactions_maintain_balance_chain(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-WF-010',
                'slip_amount' => 50000,
                'physical_cash_amount' => 50000,
            ])
            ->assertRedirect();

        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-WF-011',
                'amount' => 20000,
                'pin' => '123456',
            ])
            ->assertRedirect();

        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-WF-012',
                'slip_amount' => 10000,
                'physical_cash_amount' => 10000,
            ])
            ->assertRedirect();

        $transactions = Transaction::orderBy('id')->get();

        $this->assertSame(3, $transactions->count());

        $expectedBalances = [
            [25000, 75000],
            [75000, 55000],
            [55000, 65000],
        ];

        foreach ($transactions->values() as $index => $transaction) {
            [$before, $after] = $expectedBalances[$index];

            $this->assertSame($before, $transaction->balance_before, "Chain broken at transaction #{$index}.");
            $this->assertSame($after, $transaction->balance_after, "Chain broken at transaction #{$index}.");
        }

        $this->assertSame(65000, $this->account->refresh()->balance);
    }

    public function test_receipt_numbers_are_unique_across_all_transaction_types(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-WF-020',
                'slip_amount' => 10000,
                'physical_cash_amount' => 10000,
            ])
            ->assertRedirect();

        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-WF-021',
                'amount' => 5000,
                'pin' => '123456',
            ])
            ->assertRedirect();

        $this->actingAsTeller()
            ->post(route('teller.transfers.store', $this->account), [
                'recipient_account_number' => $this->destination->account_number,
                'amount' => 5000,
                'pin' => '123456',
            ])
            ->assertRedirect();

        $receiptNumbers = Transaction::pluck('receipt_number')->toArray();

        $this->assertCount(3, $receiptNumbers);
        $this->assertCount(3, array_unique($receiptNumbers));
        $this->assertNotContains('', $receiptNumbers);
    }

    public function test_json_deposit_returns_created_payload(): void
    {
        $this->actingAsTeller()
            ->postJson(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-WF-030',
                'slip_amount' => 50000,
                'physical_cash_amount' => 50000,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', 50000)
            ->assertJsonPath('data.balance_after', 75000);

        $this->assertSame(75000, $this->account->refresh()->balance);
    }

    public function test_json_withdrawal_returns_created_payload(): void
    {
        $this->actingAsTeller()
            ->postJson(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-WF-031',
                'amount' => 5000,
                'pin' => '123456',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.balance_after', 20000);
    }

    public function test_transfer_http_rejects_unknown_recipient_account_number(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.transfers.create', $this->account))
            ->post(route('teller.transfers.store', $this->account), [
                'recipient_account_number' => '1999/1999-XXX',
                'amount' => 5000,
                'pin' => '123456',
            ])
            ->assertRedirect(route('teller.transfers.create', $this->account))
            ->assertSessionHasErrors('recipient_account_number');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
        $this->assertSame(5000, $this->destination->refresh()->balance);
    }

    public function test_validation_rejects_negative_amounts(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-WF-040',
                'slip_amount' => -5000,
                'physical_cash_amount' => -5000,
            ])
            ->assertSessionHasErrors('slip_amount');

        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-WF-041',
                'amount' => -5000,
                'pin' => '123456',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_supervisor_cannot_post_transactions(): void
    {
        $this->actingAs($this->supervisor, 'web')
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-WF-050',
                'slip_amount' => 10000,
                'physical_cash_amount' => 10000,
            ])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Transaction::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
    }
}
