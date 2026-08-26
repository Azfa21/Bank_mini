<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\CashTransfer;
use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\DocumentSequence;
use App\Models\JurnalAkuntansi;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetNasabahData extends Command
{
    protected $signature = 'bankmini:reset-nasabah {--force : Lewati prompt konfirmasi}';

    protected $description = 'Membersihkan seluruh data nasabah, transaksi, jurnal, dan laporan hasil testing untuk peluncuran (akun staff & konfigurasi sistem tetap utuh)';

    public function handle(): int
    {
        $targets = [
            'jurnal_akuntansi' => JurnalAkuntansi::class,
            'transactions' => Transaction::class,
            'cash_transfers' => CashTransfer::class,
            'daily_reports' => DailyReport::class,
            'accounts' => Account::class,
            'customers' => Customer::class,
            'document_sequences' => DocumentSequence::class,
            'audit_logs' => AuditLog::class,
        ];

        if (! $this->option('force')) {
            $this->warn('Perintah ini akan MENGHAPUS PERMANEN data berikut:');
            foreach ($targets as $table => $model) {
                $this->line(sprintf('  %-20s %s baris', $table, number_format($model::query()->count())));
            }

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
                $deleted[$table] = $model::query()->delete();
            }

            return $deleted;
        });

        $this->table(
            ['Tabel', 'Baris Terhapus'],
            collect($deleted)->map(fn ($count, $table) => [$table, number_format($count)])->values()
        );

        $this->newLine();
        $this->info('Data nasabah berhasil dibersihkan. Nomor dokumen (NIS/no. rekening/no. bukti) mulai dari awal kembali.');

        return self::SUCCESS;
    }
}
