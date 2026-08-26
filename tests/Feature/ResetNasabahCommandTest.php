<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\CashTransfer;
use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\DocumentSequence;
use App\Models\JurnalAkuntansi;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class ResetNasabahCommandTest extends TellerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-RESET-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'amount' => 100000,
        ], $this->teller);

        DailyReport::create([
            'report_number' => 'RPT-RESET-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 100000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 100000,
            'closing_balance_physical' => 100000,
            'cash_difference' => 0,
            'status' => 'draft',
        ]);
    }

    public function test_reset_deletes_nasabah_related_data_and_keeps_users_and_config(): void
    {
        $this->assertGreaterThan(0, Customer::count());
        $this->assertGreaterThan(0, JurnalAkuntansi::count());
        $this->assertGreaterThan(0, DocumentSequence::count());
        $this->assertGreaterThan(0, AuditLog::count());

        $this->artisan('bankmini:reset-nasabah', ['--force' => true])
            ->assertExitCode(0)
            ->run();

        foreach ([JurnalAkuntansi::class, Transaction::class, CashTransfer::class, DailyReport::class, Account::class, Customer::class, DocumentSequence::class, AuditLog::class] as $model) {
            $this->assertSame(0, $model::query()->count(), "Tabel {$model} harus kosong setelah reset.");
        }

        $this->assertSame(3, User::query()->count());
        $this->assertSame('10000', SystemConfig::where('config_key', 'minimum_balance')->value('config_value'));
    }

    public function test_reset_can_be_cancelled_at_confirmation(): void
    {
        $this->artisan('bankmini:reset-nasabah')
            ->expectsConfirmation('Lanjutkan reset data nasabah?', 'no')
            ->expectsOutputToContain('Reset dibatalkan.')
            ->assertSuccessful();

        $this->assertSame(1, Customer::query()->count());
        $this->assertSame(1, Account::query()->count());
    }
}
