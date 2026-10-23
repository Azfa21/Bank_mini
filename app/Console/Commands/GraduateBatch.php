<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use App\Services\GraduationService;
use Illuminate\Console\Command;

class GraduateBatch extends Command
{
    protected $signature = 'bankmini:graduate
                            {--year= : Angkatan kelulusan, misalnya 2026}
                            {--operator= : Username Administrator yang dicatat pada audit log}
                            {--dry-run : Tampilkan rencana tanpa mengubah data}';

    protected $description = 'Mengarsipkan satu angkatan lulus: anggota dan penabung dengan sisa saldo tinggal saldo mengendap diproses, sisanya menunggu loket';

    public function handle(GraduationService $graduations): int
    {
        $year = trim((string) ($this->option('year') ?: $this->ask('Angkatan kelulusan yang diproses')));

        if ($year === '') {
            $this->error('Angkatan kelulusan wajib diisi.');

            return self::FAILURE;
        }

        $rows = $graduations->membersFor($year);

        if ($rows->isEmpty()) {
            $this->warn("Tidak ada anggota dengan angkatan kelulusan {$year}.");

            return self::SUCCESS;
        }

        $this->table(
            ['NIS', 'Nama', 'Saldo', 'Keadaan'],
            $rows->map(fn (Member $member) => [
                $member->nis,
                $member->full_name,
                'Rp '.number_format((int) ($member->saver?->account->balance ?? 0), 0, ',', '.'),
                $this->stateLabel($graduations->stateFor($member)),
            ])->all(),
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run: tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Proses kelulusan angkatan {$year} sekarang?", true)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $operator = null;
        $username = $this->option('operator');

        if ($username) {
            $operator = User::query()->where('username', $username)->where('role', 'admin')->first();

            if ($operator === null) {
                $this->error("Administrator dengan username {$username} tidak ditemukan.");

                return self::FAILURE;
            }
        }

        $result = $graduations->graduateBatch($year, $operator);

        $this->info("{$result['archived']} siswa diarsipkan, {$result['pending_counter']} menunggu penyelesaian di loket.");

        return self::SUCCESS;
    }

    protected function stateLabel(string $state): string
    {
        return match ($state) {
            'alumni' => 'Sudah alumni',
            'roster_only' => 'Tanpa rekening, siap diarsipkan',
            'ready' => 'Sisa saldo hanya saldo mengendap, siap ditutup',
            'pending_approval' => 'Menunggu persetujuan penutupan',
            'pending_counter' => 'Menunggu loket (saldo di atas saldo mengendap)',
            default => $state,
        };
    }
}
