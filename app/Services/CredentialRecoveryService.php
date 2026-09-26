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

class CredentialRecoveryService
{
    public const TYPE_STAFF = 'staff';

    public const TYPE_NASABAH = 'nasabah';

    public function __construct(
        protected MobileTokenService $mobileTokens,
    ) {}

    /**
     * @return array{ok: bool, status: int, message: string, field: string}
     */
    public function requestOtp(string $accountType, string $email): array
    {
        $email = mb_strtolower(trim($email));
        $account = $this->findAccount($accountType, $email);

        if ($account === null) {
            $this->record($accountType, '_otp_request_failed', null, [
                'email' => $email,
                'reason' => 'not_found',
            ]);

            return $this->result(false, 422, 'email', 'Email belum terdaftar pada sistem. Hubungi Administrator untuk mendaftarkan email Anda.');
        }

        if ($account->status !== 'active') {
            $this->record($accountType, '_otp_request_blocked', $account->id, [
                'email' => $email,
            ]);

            return $this->result(false, 403, 'email', 'Akun Anda diblokir. Silakan hubungi Administrator.');
        }

        $cooldown = (int) config('recovery.otp_resend_cooldown_seconds', 60);
        $latest = $this->latestRecord($accountType, $email);

        if ($latest !== null && $latest->created_at !== null && $latest->created_at->gt(now()->subSeconds($cooldown))) {
            $readyAt = $latest->created_at->copy()->addSeconds($cooldown);
            $wait = max(1, $readyAt->getTimestamp() - now()->getTimestamp());

            return $this->result(false, 429, 'email', 'Mohon tunggu '.$wait.' detik sebelum meminta kode verifikasi lagi.');
        }

        $maxPerHour = (int) config('recovery.otp_max_per_hour', 5);
        $recent = PasswordResetOtp::query()
            ->where('account_type', $accountType)
            ->where('email', $email)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= $maxPerHour) {
            $this->record($accountType, '_otp_request_throttled', $account->id, [
                'email' => $email,
            ]);

            return $this->result(false, 429, 'email', 'Terlalu banyak permintaan kode verifikasi. Silakan coba lagi nanti.');
        }

        $code = $this->generateCode();
        $expiresAt = now()->addMinutes((int) config('recovery.otp_expire_minutes', 10));

        DB::transaction(function () use ($accountType, $email, $code, $expiresAt) {
            PasswordResetOtp::query()
                ->where('account_type', $accountType)
                ->where('email', $email)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            PasswordResetOtp::create([
                'account_type' => $accountType,
                'email' => $email,
                'otp_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => $expiresAt,
            ]);
        });

        Mail::to($account->email)->send(new ResetOtpMail(
            code: $code,
            purpose: $accountType === self::TYPE_NASABAH ? 'pin' : 'password',
            recipientName: $account->full_name,
        ));

        $this->record($accountType, '_otp_requested', $account->id, [
            'email' => $email,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return $this->result(true, 200, 'email', 'Kode verifikasi telah dikirim ke email Anda. Kode berlaku '.config('recovery.otp_expire_minutes', 10).' menit.');
    }

    /**
     * @return array{ok: bool, status: int, message: string, field: string}
     */
    public function verifyAndReset(string $accountType, string $email, string $otp, string $newSecret): array
    {
        $email = mb_strtolower(trim($email));
        $account = $this->findAccount($accountType, $email);

        if ($account === null) {
            $this->record($accountType, '_failed', null, [
                'email' => $email,
                'reason' => 'not_found',
            ]);

            return $this->result(false, 422, 'email', 'Data akun tidak ditemukan.');
        }

        if ($account->status !== 'active') {
            $this->record($accountType, '_blocked', $account->id, [
                'email' => $email,
            ]);

            return $this->result(false, 403, 'email', 'Akun Anda diblokir. Silakan hubungi Administrator.');
        }

        $attempts = max(1, (int) config('recovery.transaction_attempts', 3));

        try {
            $result = DB::transaction(function () use ($accountType, $email, $otp, $account, $newSecret) {
                $record = $this->latestRecord($accountType, $email, lockForUpdate: true);

                if ($record === null || ! $record->isUsable()) {
                    $this->record($accountType, '_otp_failed', $account->id, [
                        'email' => $email,
                        'reason' => 'expired_or_missing',
                    ]);

                    return $this->result(false, 422, 'otp', 'Kode verifikasi tidak valid atau sudah kedaluwarsa. Silakan minta kode baru.');
                }

                $maxAttempts = (int) config('recovery.otp_max_attempts', 5);

                if (! Hash::check($otp, $record->otp_hash)) {
                    $record->increment('attempts');
                    $record->refresh();

                    if ($record->attempts >= $maxAttempts) {
                        $record->update(['consumed_at' => now()]);

                        $this->record($accountType, '_otp_failed', $account->id, [
                            'email' => $email,
                            'reason' => 'max_attempts',
                        ]);

                        return $this->result(false, 429, 'otp', 'Kode verifikasi salah. Batas percobaan tercapai, silakan minta kode baru.');
                    }

                    $remaining = max(0, $maxAttempts - $record->attempts);

                    $this->record($accountType, '_otp_failed', $account->id, [
                        'email' => $email,
                        'reason' => 'invalid_otp',
                        'attempts' => $record->attempts,
                    ]);

                    return $this->result(false, 422, 'otp', 'Kode verifikasi salah. Sisa percobaan: '.$remaining.'.');
                }

                $consumed = PasswordResetOtp::query()
                    ->whereKey($record->id)
                    ->whereNull('consumed_at')
                    ->update(['consumed_at' => now()]);

                if ($consumed !== 1) {
                    $this->record($accountType, '_otp_failed', $account->id, [
                        'email' => $email,
                        'reason' => 'already_used',
                    ]);

                    return $this->result(false, 422, 'otp', 'Kode verifikasi tidak valid atau sudah kedaluwarsa. Silakan minta kode baru.');
                }

                if ($accountType === self::TYPE_NASABAH) {
                    $account->update([
                        'pin_hash' => $newSecret,
                        'failed_pin_attempts' => 0,
                        'locked_until' => null,
                    ]);
                    $this->mobileTokens->revokeAll($account);
                } else {
                    $account->update([
                        'password_hash' => $newSecret,
                    ]);
                }

                return $this->result(true, 200, 'otp', 'Kredensial berhasil diperbarui. Silakan masuk menggunakan kredensial baru Anda.');
            }, $attempts);
        } catch (QueryException|DeadlockException $exception) {
            if (! app(ConcurrencyErrorDetector::class)->causedByConcurrencyError($exception)) {
                throw $exception;
            }

            $this->record($accountType, '_otp_failed', $account->id, [
                'email' => $email,
                'reason' => 'lock_contention',
            ]);

            return $this->result(false, 409, 'otp', 'Permintaan lain sedang diproses. Silakan coba lagi.');
        }

        if (! $result['ok']) {
            return $result;
        }

        $this->record($accountType, '_success', $account->id, [
            'email' => $email,
        ]);

        $label = $accountType === self::TYPE_NASABAH ? 'PIN' : 'Password';

        return $this->result(true, 200, 'otp', $label.' berhasil diperbarui. Silakan masuk menggunakan '.$label.' baru Anda.');
    }

    protected function findAccount(string $accountType, string $email): User|Saver|null
    {
        if ($accountType === self::TYPE_STAFF) {
            return User::query()->where('email', $email)->first();
        }

        return Saver::query()->where('email', $email)->first();
    }

    protected function latestRecord(string $accountType, string $email, bool $lockForUpdate = false): ?PasswordResetOtp
    {
        return PasswordResetOtp::query()
            ->where('account_type', $accountType)
            ->where('email', $email)
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
            $this->actionPrefix($accountType).$suffix,
            $this->entityName($accountType),
            $entityId,
            $metadata,
        );
    }

    protected function actionPrefix(string $accountType): string
    {
        return $accountType === self::TYPE_NASABAH ? 'pin_reset' : 'password_reset';
    }

    protected function entityName(string $accountType): string
    {
        return $accountType === self::TYPE_NASABAH ? 'saver' : 'user';
    }

    /**
     * @return array{ok: bool, status: int, message: string, field: string}
     */
    protected function result(bool $ok, int $status, string $field, string $message): array
    {
        return compact('ok', 'status', 'field', 'message');
    }
}
