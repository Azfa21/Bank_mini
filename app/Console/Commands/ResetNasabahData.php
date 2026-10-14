<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\CashTransfer;
use App\Models\DailyReport;
use App\Models\DocumentSequence;
use App\Models\JurnalAkuntansi;
use App\Models\Member;
use App\Models\Saver;
use App\Models\Transaction;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetNasabahData extends Command
{
    protected $signature = 'bankmini:reset-nasabah
                            {--force : Lewati prompt konfirmasi}
                            {--operator= : Nama operator yang menjalankan reset, dicatat pada audit log}';

    protected $description = 'Menghapus permanen data nasabah, transaksi, jurnal, dan laporan hasil testing untuk peluncuran (hanya non-production; akun staff & konfigurasi sistem tetap utuh)';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Perintah ini diblokir pada environment production.');
            $this->line('Anggota yang sudah menabung tidak boleh dihapus permanen setelah peluncuran.');

            return self::FAILURE;
        }

        $targets = [
            'jurnal_akuntansi' => JurnalAkuntansi::class,
            'transactions' => Transaction::class,
            'cash_transfers' => CashTransfer::class,
            'daily_reports' => DailyReport::class,
            'accounts' => Account::class,
            'savers' => Saver::class,
            'members' => Member::class,
            'document_sequences' => DocumentSequence::class,
            'audit_logs' => AuditLog::class,
        ];

        if (! $this->option('force')) {
            $this->warn('Perintah ini akan MENGHAPUS PERMANEN data berikut:');
            foreach (array_keys($targets) as $table) {
                $this->line(sprintf('  %-20s %s baris', $table, number_format(DB::table($table)->count())));
            }

            $this->newLine();
            $this->warn('Aturan aplikasi: anggota yang sudah menabung tidak dapat dihapus permanen karena');
            $this->warn('histori transaksi dan jurnalnya wajib tetap tersimpan. Perintah ini sengaja');
            $this->warn('melanggar aturan tersebut untuk membersihkan data testing sebelum peluncuran,');
            $this->warn('sehingga data yang terhapus TIDAK dapat dikembalikan.');
            $this->newLine();
            $this->info('Akun staff (admin/teller/supervisor), password, dan konfigurasi sistem TIDAK akan diubah.');

            if (! $this->confirm('Lanjutkan reset data nasabah?')) {
                $this->info('Reset dibatalkan.');

                return self::SUCCESS;
            }
        }

        $deleted = DB::transaction(function () use ($targets) {
            $deleted = [];

            foreach ($targets as $table => $model) {
                // forceDelete() melakukan hard delete. Model ber-soft delete (mis. savers)
                // akan menyisakan baris bila memakai delete() dan menahan foreign key ke members.
                $deleted[$table] = $model::query()->forceDelete();
            }

            return $deleted;
        });

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
        $this->info('Data nasabah berhasil dibersihkan. Nomor dokumen (NIS/no. rekening/no. bukti) mulai dari awal kembali.');

        return self::SUCCESS;
    }
}
