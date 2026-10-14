<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\DocumentSequence;
use App\Models\JurnalAkuntansi;
use App\Models\Member;
use App\Models\Saver;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;
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
        $this->assertGreaterThan(0, Saver::count());
        $this->assertGreaterThan(0, JurnalAkuntansi::count());
        $this->assertGreaterThan(0, DocumentSequence::count());
        $this->assertGreaterThan(0, AuditLog::count());

        $this->artisan('bankmini:reset-nasabah', ['--force' => true])
            ->assertExitCode(0)
            ->run();

        foreach ([JurnalAkuntansi::class, Transaction::class, CashTransfer::class, DailyReport::class, Account::class, Saver::class, DocumentSequence::class] as $model) {
            $this->assertSame(0, $model::query()->count(), "Tabel {$model} harus kosong setelah reset.");
        }

        // Audit log lama ikut terhapus, tetapi jejak reset ditulis setelah commit sehingga bertahan.
        $this->assertSame(1, AuditLog::query()->count());
        $this->assertSame('reset_nasabah', AuditLog::query()->value('action'));

        $this->assertSame(3, User::query()->count());
        $this->assertSame('10000', SystemConfig::where('config_key', 'minimum_balance')->value('config_value'));
    }

    public function test_reset_hard_deletes_savers_so_linked_members_can_be_removed(): void
    {
        $member = Member::create([
            'nis' => '2023777',
            'full_name' => 'Anggota Uji Reset',
            'status' => Member::STATUS_ACCOUNT_ACTIVE,
            'created_by' => $this->admin->id,
        ]);

        Saver::create([
            'student_number' => '2023778',
            'member_nis' => $member->nis,
            'full_name' => 'Penabung Terhubung Anggota',
            'class_name' => 'X RPL 1',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-reset-member-link',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        // Model Saver memakai soft delete: delete() menyisakan baris yang menahan
        // foreign key savers.member_nis -> members.nis. Reset wajib hard delete.
        Saver::query()->whereKey($this->nasabah->id)->delete();
        $this->assertNotNull(Saver::withTrashed()->findOrFail($this->nasabah->id)->deleted_at);

        $this->artisan('bankmini:reset-nasabah', ['--force' => true])
            ->assertExitCode(0)
            ->run();

        $this->assertSame(0, Member::query()->count());
        $this->assertSame(0, Saver::withTrashed()->count());
    }

    public function test_reset_is_blocked_in_production_environment(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('bankmini:reset-nasabah', ['--force' => true])
            ->expectsOutputToContain('diblokir pada environment production')
            ->assertFailed();

        $this->assertGreaterThan(0, Saver::withTrashed()->count());
        $this->assertGreaterThan(0, Transaction::query()->count());
        $this->assertGreaterThan(0, JurnalAkuntansi::query()->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'reset_nasabah')->count());
    }

    public function test_reset_writes_audit_log_that_survives_the_wipe(): void
    {
        $this->artisan('bankmini:reset-nasabah', ['--force' => true, '--operator' => 'Supervisor Uji'])
            ->assertExitCode(0)
            ->run();

        $this->assertSame(1, AuditLog::query()->count());
        $log = AuditLog::query()->firstOrFail();

        $this->assertSame('reset_nasabah', $log->action);
        $this->assertSame('data_nasabah', $log->entity_name);
        $this->assertSame('Supervisor Uji', $log->metadata['operator']);
        $this->assertSame('testing', $log->metadata['environment']);
        $this->assertSame(1, $log->metadata['deleted']['savers']);
    }

    public function test_confirmation_counts_soft_deleted_rows_that_will_be_deleted(): void
    {
        // Baris savers yang sudah soft delete tetap ikut terhapus permanen, jadi harus terhitung.
        Saver::query()->whereKey($this->nasabah->id)->delete();

        $this->artisan('bankmini:reset-nasabah')
            ->expectsOutputToContain('savers               1 baris')
            ->expectsConfirmation('Lanjutkan reset data nasabah?', 'no')
            ->assertSuccessful();

        $this->assertSame(1, Saver::withTrashed()->count());
    }

    public function test_reset_can_be_cancelled_at_confirmation(): void
    {
        $this->artisan('bankmini:reset-nasabah')
            ->expectsConfirmation('Lanjutkan reset data nasabah?', 'no')
            ->expectsOutputToContain('Reset dibatalkan.')
            ->assertSuccessful();

        $this->assertSame(1, Saver::query()->count());
        $this->assertSame(1, Account::query()->count());
    }
}
