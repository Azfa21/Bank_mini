<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    /**
     * Mengambil nomor urut berikutnya untuk sebuah key secara atomik.
     *
     * Dipanggil di dalam transaksi DB sehingga baris sequence dikunci
     * (lockForUpdate) dan proses lain yang berjalan bersamaan akan
     * menunggu hingga transaksi pertama selesai (anti race condition).
     */
    public function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $sequence = DocumentSequence::query()
                ->lockForUpdate()
                ->firstOrCreate(['key' => $key], ['last_number' => 0]);

            $next = $sequence->last_number + 1;

            $sequence->update(['last_number' => $next]);

            return $next;
        });
    }

    public function formatted(string $key, string $prefix, int $length = 4): string
    {
        return $prefix.'-'.str_pad($this->next($key), $length, '0', STR_PAD_LEFT);
    }
}
