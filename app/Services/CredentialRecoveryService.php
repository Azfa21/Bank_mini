<?php

namespace App\Services;

use App\Mail\ResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\Saver;
use App\Models\User;
use Illuminate\Contracts\Database\ConcurrencyErrorDetector;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Kode verifikasi hanya boleh sampai ke email yang benar-benar tercatat pada
 * akun pemiliknya: kolom `email` di tabel `users` untuk pegawai dan di tabel
 * `savers` untuk penabung. Anggota yang belum diaktivasi belum punya baris
 * `savers`, jadi tidak punya email penerima: permintaannya ditolak dengan
 * pesan jelas, bukan diberi notifikasi "terkirim" palsu.
 */
class CredentialRecoveryService
{
    public const TYPE_STAFF = 'staff';

    public const TYPE_NASABAH = 'nasabah';

    public function __construct(
        protected MobileTokenService $mobileTokens,
    ) {}

    /**
     * @return array{ok: bool, status: int, message: string, field: string, email: string|null}
     */
    public function requestOtp(string $accountType, string $identifier): array
    {
        $identifier = $this->normalize($identifier);
        $account = $this->findAccount($accountType, $identifier);

        if ($account === null) {
            $this->record($accountType, '_otp_request_ignored', null, [
                'identifier' => $identifier,
                'reason' => 'not_found',
            ]);

            return $this->result(false, 404, 'identifier', $this->notFoundMessage($accountType));
        }

        if ($account->status !== 'active') {
            $this->record($accountType, '_otp_request_ignored', $account->id, [
                'identifier' => $identifier,
                'reason' => 'inactive',
            ]);

            return $this->result(false, 403, 'identifier', 'Akun ini diblokir dan tidak dapat dipulihkan. Hubungi Administrator.');
        }

        $email = $this->registeredEmail($account);

        if ($email === null) {
            $this->record($accountType, '_otp_request_ignored', $account->id, [
                'identifier' => $identifier,
                'reason' => 'no_email',
            ]);

            return $this->result(false, 422, 'identifier', $this->missingEmailMessage($accountType), null);
        }

        $cooldown = (int) config('recovery.otp_resend_cooldown_seconds', 60);
        $latest = $this->latestRecord($accountType, $account->id);

        if ($latest !== null && $latest->created_at !== null && $latest->created_at->gt(now()->subSeconds($cooldown))) {
            $this->record($accountType, '_otp_request_throttled', $account->id, [
                'identifier' => $identifier,
                'cooldown_seconds' => $cooldown,
            ]);

            $readyAt = $latest->created_at->copy()->addSeconds($cooldown);
            $wait = max(1, $readyAt->getTimestamp() - now()->getTimestamp());

            return $this->result(true, 200, 'identifier', 'Kode verifikasi sudah dikirim ke '.$email.'. Tunggu '.$wait.' detik sebelum meminta kode baru.', $email);
        }

        $maxPerHour = (int) config('recovery.otp_max_per_hour', 5);
        $recent = PasswordResetOtp::query()
            ->where('account_type', $accountType)
            ->where('account_id', $account->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= $maxPerHour) {
            $this->record($accountType, '_otp_request_throttled', $account->id, [
                'identifier' => $identifier,
                'reason' => 'hourly_limit',
            ]);

            return $this->result(false, 429, 'identifier', 'Terlalu banyak permintaan kode verifikasi dalam satu jam terakhir. Coba lagi nanti.');
        }

        $code = $this->generateCode();
        $expiresAt = now()->addMinutes((int) config('recovery.otp_expire_minutes', 10));

        DB::transaction(function () use ($accountType, $account, $code, $expiresAt, $email) {
            PasswordResetOtp::query()
                ->where('account_type', $accountType)
                ->where('account_id', $account->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            PasswordResetOtp::create([
                'account_type' => $accountType,
                'account_id' => $account->id,
                'email' => $email,
                'otp_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => $expiresAt,
            ]);
        });

        Mail::to($email)->send(new ResetOtpMail(
            code: $code,
            recipientName: $account->full_name,
        ));

        $this->record($accountType, '_otp_requested', $account->id, [
            'identifier' => $identifier,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return $this->result(true, 200, 'identifier', 'Kode verifikasi telah dikirim ke email terdaftar.', $email);
    }

    /**
     * @return array{ok: bool, status: int, message: string, field: string, email: string|null}
     */
    public function verifyAndReset(string $accountType, string $identifier, string $otp, string $newSecret): array
    {
        $identifier = $this->normalize($identifier);
        $account = $this->findAccount($accountType, $identifier);
        $email = $account !== null ? $this->registeredEmail($account) : null;

        if ($account === null) {
            $this->record($accountType, '_failed', null, [
                'identifier' => $identifier,
                'reason' => 'not_found',
            ]);

            return $this->result(false, 422, 'otp', 'Kode verifikasi salah atau sudah kedaluwarsa. Silakan minta kode baru.');
        }

        if ($account->status !== 'active') {
            $this->record($accountType, '_blocked', $account->id, [
                'identifier' => $identifier,
            ]);

            return $this->result(false, 403, 'otp', 'Akun ini tidak dapat dipulihkan. Hubungi Administrator.', $email);
        }

        $attempts = max(1, (int) config('recovery.transaction_attempts', 3));

        try {
            $result = DB::transaction(function () use ($accountType, $identifier, $account, $otp, $newSecret, $email) {
                $record = $this->latestRecord($accountType, $account->id, lockForUpdate: true);

                if ($record === null || ! $record->isUsable()) {
                    $this->record($accountType, '_otp_failed', $account->id, [
                        'identifier' => $identifier,
                        'reason' => 'expired_or_missing',
                    ]);

                    return $this->result(false, 422, 'otp', 'Kode verifikasi salah atau sudah kedaluwarsa. Silakan minta kode baru.', $email);
                }

                $maxAttempts = (int) config('recovery.otp_max_attempts', 5);

                if (! Hash::check($otp, $record->otp_hash)) {
                    $record->increment('attempts');
                    $record->refresh();

                    if ($record->attempts >= $maxAttempts) {
                        $record->update(['consumed_at' => now()]);

                        $this->record($accountType, '_otp_failed', $account->id, [
                            'identifier' => $identifier,
                            'reason' => 'max_attempts',
                        ]);

                        return $this->result(false, 429, 'otp', 'Kode verifikasi salah. Batas percobaan tercapai, silakan minta kode baru.', $email);
                    }

                    $remaining = max(0, $maxAttempts - $record->attempts);

                    $this->record($accountType, '_otp_failed', $account->id, [
                        'identifier' => $identifier,
                        'reason' => 'invalid_otp',
                        'attempts' => $record->attempts,
                    ]);

                    return $this->result(false, 422, 'otp', 'Kode verifikasi salah. Sisa percobaan: '.$remaining.'.', $email);
                }

                $consumed = PasswordResetOtp::query()
                    ->whereKey($record->id)
                    ->whereNull('consumed_at')
                    ->update(['consumed_at' => now()]);

                if ($consumed !== 1) {
                    $this->record($accountType, '_otp_failed', $account->id, [
                        'identifier' => $identifier,
                        'reason' => 'already_used',
                    ]);

                    return $this->result(false, 422, 'otp', 'Kode verifikasi salah atau sudah kedaluwarsa. Silakan minta kode baru.', $email);
                }

                if ($accountType === self::TYPE_NASABAH) {
                    // Pemulihan lewat OTP membuat password login baru, termasuk
                    // untuk penabung lama yang belum pernah punya password. PIN
                    // transaksi tidak disentuh: kredensial itu hanya di loket.
                    $account->update([
                        'password_hash' => $newSecret,
                        'failed_pin_attempts' => 0,
                        'locked_until' => null,
                    ]);
                    $this->mobileTokens->revokeAll($account);
                } else {
                    $account->update([
                        'password_hash' => $newSecret,
                    ]);
                }

                return $this->result(true, 200, 'otp', 'Password berhasil diperbarui. Silakan masuk memakai yang baru.', $email);
            }, $attempts);
        } catch (QueryException|DeadlockException $exception) {
            if (! app(ConcurrencyErrorDetector::class)->causedByConcurrencyError($exception)) {
                throw $exception;
            }

            $this->record($accountType, '_otp_failed', $account->id, [
                'identifier' => $identifier,
                'reason' => 'lock_contention',
            ]);

            return $this->result(false, 409, 'otp', 'Permintaan lain sedang diproses. Silakan coba lagi.', $email);
        }

        if (! $result['ok']) {
            return $result;
        }

        $this->record($accountType, '_success', $account->id, [
            'identifier' => $identifier,
        ]);

        return $this->result(true, 200, 'otp', 'Password berhasil diperbarui. Silakan masuk menggunakan password baru Anda.', $email);
    }

    /**
     * Email yang benar-benar terdaftar pada akun. Alamat ini yang dipakai
     * sebagai tujuan kode verifikasi, sekaligus yang ditampilkan kembali ke
     * pemilik akun di halaman pemulihan agar ia tahu kotak masuk mana yang
     * harus dibuka. Akun tanpa email mengembalikan null.
     */
    protected function registeredEmail(User|Saver $account): ?string
    {
        $email = $account->email;

        if (! is_string($email)) {
            return null;
        }

        $email = trim($email);

        return $email === '' ? null : $email;
    }

    protected function notFoundMessage(string $accountType): string
    {
        return $accountType === self::TYPE_NASABAH
            ? 'Nomor siswa belum terdaftar sebagai penabung. Aktivasi rekening penabung dulu di loket teller.'
            : 'Username tidak terdaftar. Periksa kembali penulisannya atau minta Administrator mengatur ulang password Anda.';
    }

    protected function missingEmailMessage(string $accountType): string
    {
        return $accountType === self::TYPE_NASABAH
            ? 'Akun penabung ini belum memiliki email terdaftar. Hubungi Teller atau Administrator untuk melengkapinya.'
            : 'Akun ini belum memiliki email terdaftar. Minta Administrator menambahkan email pada akun Anda.';
    }

    /**
     * Sama dengan form login: spasi yang tidak sengaja ikut diketik tidak
     * boleh membuat orang mengira identifier-nya salah.
     */
    protected function normalize(string $identifier): string
    {
        return trim($identifier);
    }

    /**
     * Akun dicari lewat identifier login, bukan lewat email.
     *
     * Email bisa saja ada di tangan orang lain: alamat salah ketik, atau
     * alamat keluarga yang dipakai bersama. Identifier login adalah barang
     * yang hanya pemilik akun yang punya: kartu rekening untuk penabung,
     * akun yang diberikan sekolah untuk pegawai.
     */
    protected function findAccount(string $accountType, string $identifier): User|Saver|null
    {
        if ($accountType === self::TYPE_STAFF) {
            return User::query()->where('username', $identifier)->first();
        }

        return Saver::query()->where('student_number', $identifier)->first();
    }

    protected function latestRecord(string $accountType, int $accountId, bool $lockForUpdate = false): ?PasswordResetOtp
    {
        return PasswordResetOtp::query()
            ->where('account_type', $accountType)
            ->where('account_id', $accountId)
            ->whereNull('consumed_at')
            ->latest('id')
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    protected function generateCode(): string
    {
        $length = max(4, (int) config('recovery.otp_length', 6));
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    protected function record(string $accountType, string $suffix, ?int $entityId, array $metadata): void
    {
        AuditLogService::record(
            'password_reset'.$suffix,
            $this->entityName($accountType),
            $entityId,
            $metadata,
        );
    }

    protected function entityName(string $accountType): string
    {
        return $accountType === self::TYPE_NASABAH ? 'saver' : 'user';
    }

    /**
     * @return array{ok: bool, status: int, message: string, field: string, email: string|null}
     */
    protected function result(bool $ok, int $status, string $field, string $message, ?string $email = null): array
    {
        return [
            'ok' => $ok,
            'status' => $status,
            'field' => $field,
            'message' => $message,
            'email' => $email,
        ];
    }
}
