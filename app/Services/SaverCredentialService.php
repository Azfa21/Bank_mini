<?php

namespace App\Services;

use App\Models\Saver;
use Illuminate\Support\Facades\DB;

/**
 * PIN transaksi dipisah dari password login supaya penabung bisa menyebut
 * PIN kepada teller pada momen transaksi tanpa membuka akunnya.
 */
class SaverCredentialService
{
    public function __construct(
        protected TransactionService $transactions,
    ) {}

    /**
     * Ganti PIN transaksi setelah PIN saat ini terverifikasi. Verifikasi
     * memakai alur loket yang sama, jadi percobaan salah di loket maupun di
     * halaman ini bernasib sama: tiga kali salah mengunci rekening 15 menit.
     */
    public function changeTransactionPin(Saver $saver, string $currentPin, string $newPin): void
    {
        $this->transactions->verifySaverPin($saver, $currentPin, 'penggantian PIN', $saver->id);

        DB::transaction(function () use ($saver, $newPin): void {
            // Menyimpan lewat model, bukan query builder, agar cast `hashed`
            // tetap membungkus PIN baru menjadi bcrypt.
            Saver::query()->lockForUpdate()->findOrFail($saver->id)->update([
                'pin_hash' => $newPin,
            ]);
        });

        // `user_id` pada audit_logs hanya menunjuk ke tabel users. Penabung
        // bukan user staf, jadi identitasnya dicatat lewat entity dan metadata.
        AuditLogService::record('customer_pin_changed', 'saver', $saver->id, [
            'student_number' => $saver->student_number,
        ]);
    }
}
