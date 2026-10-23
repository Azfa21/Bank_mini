<?php

namespace Tests\Feature\Mobile;

use App\Models\MobileToken;

class MobileAuthTest extends MobileTestCase
{
    public function test_login_success_returns_token_customer_and_account(): void
    {
        $response = $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'password' => 'password123',
            'device_name' => 'Pixel Test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer.student_number', '2023001')
            ->assertJsonPath('data.account.balance', 25000);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('mobile_tokens', [
            'saver_id' => $this->nasabah->id,
            'name' => 'Pixel Test',
        ]);
    }

    public function test_login_with_wrong_password_returns_unauthorized(): void
    {
        $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nomor siswa atau password tidak valid.');

        $this->assertSame(0, $this->nasabah->fresh()->failed_pin_attempts);
        $this->assertNull($this->nasabah->fresh()->locked_until);
    }

    public function test_login_rejects_password_shorter_than_eight_characters(): void
    {
        $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'password' => '1605',
        ])->assertStatus(422);
    }

    public function test_login_with_unknown_student_number_returns_validation_error(): void
    {
        $this->postJson(route('api.mobile.login'), [
            'student_number' => '0000000',
            'password' => 'password123',
        ])->assertStatus(422);
    }

    /**
     * Penguncian PIN hanya berlaku untuk verifikasi transaksi di loket.
     * Percobaan login yang gagal tidak boleh menghitung counter PIN.
     */
    public function test_failed_login_does_not_touch_the_transaction_pin_counters(): void
    {
        $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'password' => 'wrong-password',
        ])->assertStatus(401);

        $fresh = $this->nasabah->fresh();

        $this->assertSame(0, $fresh->failed_pin_attempts);
        $this->assertNull($fresh->locked_until);
        $this->assertFalse($fresh->isPinLocked());
    }

    public function test_login_rejects_blocked_account(): void
    {
        $this->nasabah->update(['status' => 'blocked']);

        $response = $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_pin_lock_does_not_block_login(): void
    {
        // Rekening terkunci karena salah PIN di loket tetap boleh masuk
        // aplikasi: dua kredensial itu sengaja dipisah.
        $this->nasabah->update([
            'failed_pin_attempts' => 3,
            'locked_until' => now()->addMinutes(15),
        ]);

        $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_protected_endpoint_requires_token(): void
    {
        $this->getJson(route('api.mobile.dashboard'))
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_protected_endpoint_rejects_invalid_token(): void
    {
        $this->withMobileToken('invalid-token')
            ->getJson(route('api.mobile.dashboard'))
            ->assertStatus(401);
    }

    public function test_expired_token_is_rejected(): void
    {
        $token = $this->loginAndGetToken();
        MobileToken::query()->update(['expires_at' => now()->subMinute()]);

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.dashboard'))
            ->assertStatus(401);
    }

    public function test_logout_revokes_token(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->postJson(route('api.mobile.logout'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, MobileToken::query()->count());

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.dashboard'))
            ->assertStatus(401);
    }

    public function test_me_returns_authenticated_customer(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.me'))
            ->assertOk()
            ->assertJsonPath('data.customer.full_name', 'Ani Wijaya');
    }
}
