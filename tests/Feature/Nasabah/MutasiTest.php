<?php

namespace Tests\Feature\Nasabah;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;

class MutasiTest extends NasabahTestCase
{
    public function test_mutasi_shows_own_transactions_with_type_and_balance(): void
    {
        $deposit = $this->deposit(50000);

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi'))
            ->assertOk()
            ->assertSee('Setoran')
            ->assertSee('Rp 50.000')
            ->assertSee('Rp 75.000')
            ->assertSee($deposit->receipt_number);
    }

    public function test_mutasi_is_scoped_to_own_account_only(): void
    {
        $this->deposit(100000);

        $other = Customer::create([
            'student_number' => '2023997',
            'full_name' => 'Budi Santoso',
            'class_name' => 'XI RPL 1',
            'phone_number' => '081234567997',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-other',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $otherAccount = Account::create([
            'account_number' => '2026/2027-002',
            'customer_id' => $other->id,
            'balance' => 50000,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);

        $otherTransaction = app(TransactionService::class)->deposit($otherAccount, [
            'slip_number' => 'SLIP-OTHER',
            'slip_amount' => 90000,
            'physical_cash_amount' => 90000,
            'amount' => 90000,
        ], $this->teller);

        $response = $this->actingAsNasabah()
            ->get(route('nasabah.mutasi'))
            ->assertOk()
            ->assertSee($this->depositReceipt(100000))
            ->assertDontSee($otherTransaction->receipt_number);
    }

    public function test_mutasi_can_filter_by_type(): void
    {
        $deposit = $this->deposit(50000);

        $withdrawal = $this->withdraw(5000);

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi', ['type' => 'deposit']))
            ->assertOk()
            ->assertSee($deposit->receipt_number)
            ->assertDontSee($withdrawal->receipt_number);

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi', ['type' => 'withdrawal']))
            ->assertOk()
            ->assertSee($withdrawal->receipt_number)
            ->assertDontSee($deposit->receipt_number);
    }

    public function test_mutasi_can_filter_by_date_range(): void
    {
        $transaction = $this->deposit(50000);

        Transaction::query()->update(['transaction_at' => now()->subDays(10)]);

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi', [
                'date_from' => now()->startOfDay()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertDontSee($transaction->receipt_number);

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi', [
                'date_from' => now()->subDays(20)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($transaction->receipt_number);
    }

    public function test_mutasi_paginates(): void
    {
        $receipts = [];
        for ($i = 0; $i < 20; $i++) {
            $receipts[] = $this->deposit(10000)->receipt_number;
        }

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi'))
            ->assertOk()
            ->assertSee('page=2');

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi', ['page' => 2]))
            ->assertOk()
            ->assertSee($receipts[0])
            ->assertDontSee($receipts[19]);
    }

    public function test_mutasi_empty_when_nasabah_has_no_account(): void
    {
        $nasabah = Customer::create([
            'student_number' => '2023996',
            'full_name' => 'Tanpa Rekening 2',
            'class_name' => 'X RPL 2',
            'phone_number' => '081234567996',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-no-account-2',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->actingAs($nasabah, 'nasabah')
            ->get(route('nasabah.mutasi'))
            ->assertOk()
            ->assertSee('belum memiliki rekening');
    }

    public function test_teller_cannot_access_nasabah_mutasi(): void
    {
        $this->actingAsTeller()
            ->get(route('nasabah.mutasi'))
            ->assertRedirect(route('login'));
    }

    public function test_nasabah_mutasi_does_not_leak_pin_or_password(): void
    {
        $this->deposit(50000);

        $this->actingAsNasabah()
            ->get(route('nasabah.mutasi'))
            ->assertOk()
            ->assertDontSee('pin_hash')
            ->assertDontSee($this->nasabah->pin_hash);
    }

    protected function deposit(int $amount): Transaction
    {
        return app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-N-'.random_int(100, 999),
            'slip_amount' => $amount,
            'physical_cash_amount' => $amount,
            'amount' => $amount,
        ], $this->teller);
    }

    protected function withdraw(int $amount): Transaction
    {
        return app(TransactionService::class)->withdraw($this->account, [
            'slip_number' => 'SLIP-N-'.random_int(100, 999),
            'amount' => $amount,
        ], '123456', $this->teller);
    }

    protected function depositReceipt(int $amount): string
    {
        return Transaction::where('type', 'deposit')->where('amount', $amount)->latest('id')->value('receipt_number');
    }
}
