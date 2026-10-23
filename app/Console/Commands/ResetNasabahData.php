<?php

namespace App\Console\Commands;

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
use App\Models\Transaction;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ResetNasabahData extends Command
{
    protected $signature = 'bankmini:reset-nasabah
                            {--force : Lewati prompt konfirmasi}
                            {--dry-run : Tampilkan jumlah baris yang akan dihapus, tanpa menghapus apa pun}
                            {--operator= : Nama operator yang menjalankan reset, dicatat pada audit log}';

    protected $description = 'Menghapus permanen data nasabah, transaksi, jurnal, laporan, sesi login, dan antrean hasil testing di environment non-production. Akun pengguna & konfigurasi sistem tetap utuh.';

    public function handle(): int
    {
        $targets = $this->targets();

        // Dry-run bersifat baca saja, jadi boleh dipakai di environment mana pun
        // untuk melihat apa yang akan dihapus sebelum peluncuran.
        if ($this->option('dry-run')) {
            $this->info('Dry-run: hanya menampilkan jumlah baris, tidak ada yang dihapus.');
            $this->renderCounts($targets);
            $this->info('Dry-run selesai. Tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        $isProduction = app()->environment('production');

        // Ini bank: begitu server berjalan sebagai production, penghapusan data
        // nasabah lewat perintah tidak boleh ada lagi, apa pun flag yang dipakai.
        if ($isProduction) {
            $this->error('Perintah ini ditolak pada environment production, tanpa flag yang bisa melewatkannya.');
            $this->line('Bersih-bersih data testing wajib dilakukan sebelum peluncuran, saat APP_ENV masih lokal atau staging.');
            $this->line('--dry-run tetap boleh dipakai di production untuk melihat jumlah baris tanpa menghapus apa pun.');

            return self::FAILURE;
        }

        if (! $this->confirmWipe($targets)) {
            return self::SUCCESS;
        }

        $deleted = $this->wipe($targets);

        // Ditulis setelah commit: audit_logs ikut dikosongkan oleh reset, jadi jejak operasi
        // harus dibuat setelahnya agar tetap tersimpan.
        AuditLogService::record(
            action: 'reset_nasabah',
            entityName: 'data_nasabah',
            metadata: [
                'environment' => app()->environment(),
                'operator' => $this->option('operator'),
                'deleted' => $deleted,
            ],
        );

        $this->table(
            ['Tabel', 'Baris Terhapus'],
            collect($deleted)->map(fn ($count, $table) => [$table, number_format($count)])->values()
        );

        $this->newLine();
        $this->info('Data testing berhasil dibersihkan. Nomor dokumen (NIS/no. rekening/no. bukti) mulai dari awal kembali.');
        $this->info('Semua sesi login (web dan aplikasi mobile) berakhir; pengguna perlu login ulang.');

        return self::SUCCESS;
    }

    /**
     * Urutan penghapusan mengikuti ketergantungan foreign key: tabel yang
     * dirujuk (daily_reports, accounts, savers, members) dibersihkan belakangan.
     * Nilai null berarti tabel tanpa model (sessions, cache, jobs) dihapus
     * langsung lewat query builder.
     *
     * @return array<string, class-string<Model>|null>
     */
    protected function targets(): array
    {
        return [
            'password_reset_otps' => PasswordResetOtp::class,
            'account_closures' => AccountClosure::class,
            'jurnal_akuntansi' => JurnalAkuntansi::class,
            'transactions' => Transaction::class,
            'cash_transfers' => CashTransfer::class,
            'daily_reports' => DailyReport::class,
            'mobile_tokens' => MobileToken::class,
            'accounts' => Account::class,
            'savers' => Saver::class,
            'members' => Member::class,
            'document_sequences' => DocumentSequence::class,
            'audit_logs' => AuditLog::class,
            'sessions' => null,
            'cache' => null,
            'cache_locks' => null,
            'jobs' => null,
            'job_batches' => null,
            'failed_jobs' => null,
        ];
    }

    /**
     * @param  array<string, class-string<Model>|null>  $targets
     */
    protected function confirmWipe(array $targets): bool
    {
        $this->warn('Perintah ini akan MENGHAPUS PERMANEN data berikut:');
        $this->renderCounts($targets);

        $this->newLine();
        $this->warn('Aturan aplikasi: anggota yang sudah menabung tidak dapat dihapus permanen karena');
        $this->warn('histori transaksi dan jurnalnya wajib tetap tersimpan. Perintah ini sengaja');
        $this->warn('melanggar aturan tersebut untuk membersihkan data testing sebelum peluncuran,');
        $this->warn('sehingga data yang terhapus TIDAK dapat dikembalikan.');
        $this->newLine();
        $this->info('Akun staff (admin/teller), password, dan konfigurasi sistem TIDAK akan diubah.');
        $this->info('Semua sesi login (web dan aplikasi mobile) akan berakhir setelah reset.');
        $this->newLine();

        if ($this->option('force')) {
            return true;
        }

        if (! $this->confirm('Lanjutkan reset data nasabah?')) {
            $this->info('Reset dibatalkan.');

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, class-string<Model>|null>  $targets
     * @return array<string, int>
     */
    protected function wipe(array $targets): array
    {
        $deleted = [];

        DB::transaction(function () use ($targets, &$deleted): void {
            foreach ($targets as $table => $model) {
                // forceDelete() melakukan hard delete. Model ber-soft delete (mis. savers)
                // akan menyisakan baris bila memakai delete() dan menahan foreign key ke members.
                $deleted[$table] = $model !== null
                    ? $model::query()->forceDelete()
                    : DB::table($table)->delete();
            }
        });

        return $deleted;
    }

    /**
     * @param  array<string, class-string<Model>|null>  $targets
     */
    protected function renderCounts(array $targets): void
    {
        foreach ($targets as $table => $model) {
            $this->line(sprintf('  %-20s %s baris', $table, number_format(DB::table($table)->count())));
        }
    }
}
