<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountClosure;
use App\Models\AuditLog;
use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\DocumentSequence;
use App\Models\JurnalAkuntansi;
use App\Models\Member;
use App\Models\MobileToken;
use App\Models\PasswordResetOtp;
use App\Models\Saver;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
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
            'status' => 'draft',
        ]);

        AccountClosure::create([
            'account_id' => $this->account->id,
            'saver_id' => $this->nasabah->id,
            'requested_by' => $this->teller->id,
            'payout_amount' => 25000,
            'status' => AccountClosure::STATUS_PENDING,
        ]);

        PasswordResetOtp::create([
            'account_type' => PasswordResetOtp::TYPE_NASABAH,
            'account_id' => $this->nasabah->id,
            'email' => 'otp-reset-test@example.test',
            'otp_hash' => 'otp-hash-reset-test',
            'expires_at' => now()->addMinutes(10),
        ]);

        MobileToken::create([
            'saver_id' => $this->nasabah->id,
            'token_hash' => 'mobile-token-hash-reset-test',
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        DB::table('sessions')->insert([
            'id' => 'reset-test-session-id',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'reset-test',
            'payload' => 'reset-test',
            'last_activity' => time(),
        ]);

        DB::table('cache')->insert([
            'key' => 'reset-test-cache-key',
            'value' => 'reset-test',
            'expiration' => time() + 60,
        ]);

        DB::table('cache_locks')->insert([
            'key' => 'reset-test-lock-key',
            'owner' => 'reset-test',
            'expiration' => time() + 60,
        ]);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{"reset":"test"}',
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        DB::table('job_batches')->insert([
            'id' => 'reset-test-batch',
            'name' => 'reset-test',
            'total_jobs' => 1,
            'pending_jobs' => 1,
            'failed_jobs' => 0,
            'failed_job_ids' => '',
            'created_at' => time(),
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => 'reset-test-uuid',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{"reset":"test"}',
            'exception' => 'reset-test exception',
            'failed_at' => now(),
        ]);
    }

    public function test_reset_deletes_all_testing_data_and_keeps_users_and_config(): void
    {
        $this->assertGreaterThan(0, Saver::count());
        $this->assertGreaterThan(0, JurnalAkuntansi::count());
        $this->assertGreaterThan(0, DocumentSequence::count());
        $this->assertGreaterThan(0, AuditLog::count());

        $this->assertExtraTargetsPopulated();

        $this->artisan('bankmini:reset-nasabah', ['--force' => true])
            ->assertExitCode(0)
            ->run();

        foreach ([
            JurnalAkuntansi::class,
            Transaction::class,
            CashTransfer::class,
            DailyReport::class,
            Account::class,
            Saver::class,
            AccountClosure::class,
            PasswordResetOtp::class,
            MobileToken::class,
            Member::class,
            DocumentSequence::class,
        ] as $model) {
            $this->assertSame(0, $model::query()->count(), "Tabel {$model} harus kosong setelah reset.");
        }

        foreach ($this->infrastructureTargets() as $table) {
            $this->assertSame(0, DB::table($table)->count(), "Tabel {$table} harus kosong setelah reset.");
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

    public function test_dry_run_only_reports_counts(): void
    {
        $this->artisan('bankmini:reset-nasabah', ['--dry-run' => true])
            ->assertSuccessful();

        // Tidak ada yang dihapus, termasuk tabel tanpa model.
        $this->assertSame(1, Saver::query()->count());
        $this->assertSame(1, Account::query()->count());
        $this->assertSame(1, DB::table('sessions')->count());
        $this->assertSame(1, DB::table('jobs')->count());

        $this->assertExtraTargetsPopulated();
        // Dry-run bersifat baca saja, jadi belum ada jejak audit reset_nasabah.
        $this->assertSame(0, AuditLog::query()->where('action', 'reset_nasabah')->count());
    }

    public function test_reset_is_refused_in_production_without_explicit_allowance(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('bankmini:reset-nasabah', ['--force' => true])
            ->expectsOutputToContain('ditolak pada environment production')
            ->expectsOutputToContain('--allow-production')
            ->assertFailed();

        $this->assertGreaterThan(0, Saver::withTrashed()->count());
        $this->assertGreaterThan(0, Transaction::query()->count());
        $this->assertGreaterThan(0, JurnalAkuntansi::query()->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'reset_nasabah')->count());
    }

    public function test_production_reset_requires_operator_name(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('bankmini:reset-nasabah', ['--force' => true, '--allow-production' => true])
            ->expectsOutputToContain('--operator wajib diisi')
            ->assertFailed();

        $this->assertSame(0, AuditLog::query()->where('action', 'reset_nasabah')->count());
        $this->assertGreaterThan(0, Saver::query()->count());
    }

    public function test_production_confirmation_cannot_be_skipped_by_force(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('bankmini:reset-nasabah', [
            '--force' => true,
            '--allow-production' => true,
            '--operator' => 'Operator Rilis',
        ])
            ->expectsQuestion('Anda di environment production. Ketik HAPUS untuk menghapus permanen data', 'ya')
            ->expectsOutputToContain('Konfirmasi tidak cocok')
            ->assertFailed();

        $this->assertGreaterThan(0, Saver::query()->count());
        $this->assertGreaterThan(0, Transaction::query()->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'reset_nasabah')->count());
    }

    public function test_production_reset_runs_after_typed_confirmation(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('bankmini:reset-nasabah', [
            '--force' => true,
            '--allow-production' => true,
            '--operator' => 'Operator Rilis',
        ])
            ->expectsQuestion('Anda di environment production. Ketik HAPUS untuk menghapus permanen data', 'HAPUS')
            ->assertSuccessful();

        $this->assertSame(0, Saver::query()->count());
        $this->assertSame(0, Transaction::query()->count());
        $this->assertSame(0, DB::table('sessions')->count());
        $this->assertSame(0, DB::table('jobs')->count());

        $this->assertSame(3, User::query()->count());

        $log = AuditLog::query()->firstOrFail();
        $this->assertSame('reset_nasabah', $log->action);
        $this->assertSame('production', $log->metadata['environment']);
        $this->assertSame('Operator Rilis', $log->metadata['operator']);
        $this->assertGreaterThan(0, $log->metadata['deleted']['savers']);
    }

    public function test_reset_writes_audit_log_that_survives_the_wipe(): void
    {
        $this->artisan('bankmini:reset-nasabah', ['--force' => true, '--operator' => 'Administrator Uji'])
            ->assertExitCode(0)
            ->run();

        $this->assertSame(1, AuditLog::query()->count());
        $log = AuditLog::query()->firstOrFail();

        $this->assertSame('reset_nasabah', $log->action);
        $this->assertSame('data_nasabah', $log->entity_name);
        $this->assertSame('Administrator Uji', $log->metadata['operator']);
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

    /** @return list<string> */
    protected function extraTargets(): array
    {
        return ['account_closures', 'password_reset_otps', 'mobile_tokens'];
    }

    /** @return list<string> */
    protected function infrastructureTargets(): array
    {
        return ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];
    }

    protected function assertExtraTargetsPopulated(): void
    {
        foreach (array_merge($this->extraTargets(), $this->infrastructureTargets()) as $table) {
            $this->assertGreaterThan(0, DB::table($table)->count(), "Tabel {$table} harus terisi sebelum reset.");
        }
    }
}
