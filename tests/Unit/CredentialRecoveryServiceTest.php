<?php

namespace Tests\Unit;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\CredentialRecoveryService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PDOException;
use Tests\TestCase;

class CredentialRecoveryServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_valid_otp_cannot_be_reused_to_reset_a_credential_twice(): void
    {
        $user = User::create([
            'username' => 'recovery-teller',
            'email' => 'recovery-teller@example.test',
            'password_hash' => Hash::make('old-password'),
            'full_name' => 'Recovery Teller',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $record = PasswordResetOtp::create([
            'account_type' => CredentialRecoveryService::TYPE_STAFF,
            'email' => $user->email,
            'otp_hash' => Hash::make('654321'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $service = app(CredentialRecoveryService::class);
        $firstResult = $service->verifyAndReset(
            CredentialRecoveryService::TYPE_STAFF,
            $user->email,
            '654321',
            Hash::make('first-password'),
        );
        $secondResult = $service->verifyAndReset(
            CredentialRecoveryService::TYPE_STAFF,
            $user->email,
            '654321',
            Hash::make('second-password'),
        );

        $this->assertTrue($firstResult['ok']);
        $this->assertFalse($secondResult['ok']);
        $this->assertTrue(Hash::check('first-password', $user->fresh()->password_hash));
        $this->assertFalse(Hash::check('second-password', $user->fresh()->password_hash));
        $this->assertNotNull($record->fresh()->consumed_at);
    }

    public function test_lock_contention_is_reported_as_conflict_without_mutating_credential(): void
    {
        $user = User::create([
            'username' => 'recovery-contention',
            'email' => 'recovery-contention@example.test',
            'password_hash' => Hash::make('old-password'),
            'full_name' => 'Recovery Contention',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $record = PasswordResetOtp::create([
            'account_type' => CredentialRecoveryService::TYPE_STAFF,
            'email' => $user->email,
            'otp_hash' => Hash::make('654321'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $throwing = true;

        DB::listen(function (QueryExecuted $event) use (&$throwing) {
            if ($throwing
                && str_contains($event->sql, 'password_reset_otps')
                && str_contains(strtolower($event->sql), 'update')) {
                throw new QueryException(
                    'sqlite',
                    $event->sql,
                    $event->bindings,
                    new PDOException('database is locked'),
                );
            }
        });

        try {
            $result = app(CredentialRecoveryService::class)->verifyAndReset(
                CredentialRecoveryService::TYPE_STAFF,
                $user->email,
                '654321',
                Hash::make('new-password'),
            );
        } finally {
            $throwing = false;
        }

        $this->assertFalse($result['ok']);
        $this->assertSame(409, $result['status']);
        $this->assertFalse(Hash::check('new-password', $user->fresh()->password_hash));
        $this->assertNull($record->fresh()->consumed_at);
    }
}
