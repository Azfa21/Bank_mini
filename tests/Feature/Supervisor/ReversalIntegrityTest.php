<?php

namespace Tests\Feature\Supervisor;

use App\Models\Account;
use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\Transaction;
use App\Services\ReversalService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Teller\TellerTestCase;

class ReversalIntegrityTest extends TellerTestCase
{
    protected function createAccount(string $name, string $studentNumber, string $accountNumber, int $balance = 0, int $minimumBalance = 10000): Account
    {
        $customer = Customer::create([
            'student_number' => $studentNumber,
            'full_name' => $name,
            'class_name' => 'X RPL 3',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-'.$studentNumber,
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        return Account::create([
            'account_number' => $accountNumber,
            'customer_id' => $customer->id,
            'balance' => $balance,
            'opening_balance' => $balance,
            'minimum_balance' => $minimumBalance,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }

    public function test_supervisor_reverses_transfer_and_restores_both_balances(): void
    {
        $destination = $this->createAccount('Budi Santoso', '2023002', '2026/2027-002');

        $transfer = app(TransactionService::class)->transfer(
            $this->account,
            $destination,
            ['amount' => 10000, 'slip_number' => 'SLIP-TRF-001'],
            '123456',
            $this->teller,
        );

        $this->assertSame(15000, $this->account->refresh()->balance);
        $this->assertSame(10000, $destination->refresh()->balance);

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.transactions.reverse', $transfer), [
                'reason' => 'Transfer salah rekening tujuan.',
            ])
            ->assertRedirect();

        $this->assertSame('reversed', $transfer->refresh()->status);
        $this->assertSame(25000, $this->account->refresh()->balance);
        $this->assertSame(0, $destination->refresh()->balance);

        $reversal = Transaction::where('type', 'reversal')->firstOrFail();
        $this->assertSame($transfer->id, $reversal->reversal_of_transaction_id);
        $this->assertSame($this->account->id, $reversal->account_id);
        $this->assertSame($destination->id, $reversal->recipient_account_id);

        $debit = (int) JurnalAkuntansi::where('transaction_id', $reversal->id)->sum('debit_amount');
        $credit = (int) JurnalAkuntansi::where('transaction_id', $reversal->id)->sum('credit_amount');
        $this->assertSame(10000, $debit);
        $this->assertSame($credit, $debit);
    }

    public function test_reverse_deposit_rejected_when_balance_already_spent(): void
    {
        $service = app(TransactionService::class);

        $deposit = $service->deposit($this->account, [
            'slip_number' => 'SLIP-REV-SPENT',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
        ], $this->teller);

        // Saldo 125.000, tarik 115.000 menyisakan 10.000 (batas minimum).
        $service->withdraw($this->account, ['amount' => 115000], '123456', $this->teller);

        $this->assertSame(10000, $this->account->refresh()->balance);

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.transactions.reverse', $deposit), [
                'reason' => 'Membatalkan setoran lama.',
            ])
            ->assertSessionHas('error');

        $this->assertSame('success', $deposit->refresh()->status);
        $this->assertSame(10000, $this->account->refresh()->balance);
        $this->assertSame(0, Transaction::where('type', 'reversal')->count());
    }

    public function test_reverse_transfer_rejected_when_recipient_balance_insufficient(): void
    {
        $destination = $this->createAccount('Citra Lestari', '2023003', '2026/2027-003');
        $service = app(TransactionService::class);

        $transfer = $service->transfer(
            $this->account,
            $destination,
            ['amount' => 15000, 'slip_number' => 'SLIP-TRF-002'],
            '123456',
            $this->teller,
        );

        $this->assertSame(10000, $this->account->refresh()->balance);
        $this->assertSame(15000, $destination->refresh()->balance);

        // Penerima memakai sebagian dana sehingga saldonya lebih kecil dari nominal transfer.
        $service->withdraw($destination, ['amount' => 5000], '123456', $this->teller);

        $this->assertSame(10000, $destination->refresh()->balance);

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.transactions.reverse', $transfer), [
                'reason' => 'Transfer dibatalkan.',
            ])
            ->assertSessionHas('error');

        $this->assertSame('success', $transfer->refresh()->status);
        $this->assertSame(10000, $this->account->refresh()->balance);
        $this->assertSame(10000, $destination->refresh()->balance);
    }

    public function test_reverse_rejected_when_daily_report_approved(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-REV-APPROVED',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        DailyReport::create([
            'report_number' => 'RPT-APPR-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 50000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 50000,
            'closing_balance_physical' => 50000,
            'cash_difference' => 0,
            'status' => 'approved',
            'approved_by' => $this->supervisor->id,
            'approved_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->supervisor, 'web')
            ->post(route('supervisor.transactions.reverse', $deposit), [
                'reason' => 'Koreksi setelah tutup kas.',
            ])
            ->assertSessionHas('error');

        $this->assertSame('success', $deposit->refresh()->status);
        $this->assertSame(0, Transaction::where('type', 'reversal')->count());
    }

    public function test_service_allows_reverse_when_report_still_submitted(): void
    {
        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-REV-SUBMITTED',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        DailyReport::create([
            'report_number' => 'RPT-SUB-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 50000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 50000,
            'closing_balance_physical' => 50000,
            'cash_difference' => 0,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $reversal = app(ReversalService::class)->reverse($deposit, $this->supervisor, 'Koreksi sebelum approval.');

        $this->assertSame('reversal', $reversal->type);
        $this->assertSame('reversed', $deposit->refresh()->status);
    }

    public function test_supervisor_report_page_renders_correction_rows(): void
    {
        $report = DailyReport::create([
            'report_number' => 'RPT-RENDER-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 50000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 50000,
            'closing_balance_physical' => 50000,
            'cash_difference' => 0,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $deposit = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-RENDER-001',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
        ], $this->teller);

        app(ReversalService::class)->reverse($deposit, $this->supervisor, 'Dikoreksi untuk pengujian tampilan.');

        $this->actingAs($this->supervisor, 'web')
            ->get(route('supervisor.reports.show', $report))
            ->assertOk()
            ->assertSee('Pembalik')
            ->assertSee('Dikoreksi');
    }
}
