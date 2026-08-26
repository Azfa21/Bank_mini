<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;

class SlipNumberGenerationTest extends TellerTestCase
{
    private function expectedSlip(int $ordinal): string
    {
        return 'SLIP-'.now()->format('Ymd').'-'.str_pad((string) $ordinal, 4, '0', STR_PAD_LEFT);
    }

    public function test_deposit_without_slip_number_generates_sequential_slips(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_amount' => 50000,
                'physical_cash_amount' => 50000,
            ])
            ->assertRedirect();

        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_amount' => 20000,
                'physical_cash_amount' => 20000,
            ])
            ->assertRedirect();

        $slips = Transaction::query()
            ->where('type', 'deposit')
            ->orderBy('id')
            ->pluck('slip_number')
            ->all();

        $this->assertSame([$this->expectedSlip(1), $this->expectedSlip(2)], $slips);
    }

    public function test_withdrawal_without_slip_number_generates_one_automatically(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'amount' => 5000,
                'pin' => '123456',
            ])
            ->assertRedirect();

        $transaction = Transaction::query()->where('type', 'withdrawal')->firstOrFail();

        $this->assertSame($this->expectedSlip(1), $transaction->slip_number);
    }

    public function test_transfer_without_slip_number_generates_one_instead_of_null(): void
    {
        $destination = $this->createDestination();

        app(TransactionService::class)->transfer(
            $this->account,
            $destination,
            ['amount' => 5000],
            '123456',
            $this->teller,
        );

        $transaction = Transaction::query()->where('type', 'transfer')->firstOrFail();

        $this->assertSame($this->expectedSlip(1), $transaction->slip_number);
    }

    public function test_explicit_slip_number_override_is_honored(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-KERTAS-999',
                'slip_amount' => 50000,
                'physical_cash_amount' => 50000,
            ])
            ->assertRedirect();

        $transaction = Transaction::query()->firstOrFail();

        $this->assertSame('SLIP-KERTAS-999', $transaction->slip_number);
    }

    private function createDestination(): Account
    {
        $customer = Customer::create([
            'student_number' => '2023002',
            'full_name' => 'Budi Santoso',
            'class_name' => 'X RPL 2',
            'phone_number' => '081234567002',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-2',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        return Account::create([
            'account_number' => '2026/2027-002',
            'customer_id' => $customer->id,
            'balance' => 10000,
            'opening_balance' => 10000,
            'minimum_balance' => 10000,
            'academic_year' => now()->format('Y').'/'.(now()->format('Y') + 1),
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }
}
