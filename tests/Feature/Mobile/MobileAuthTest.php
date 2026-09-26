<?php

namespace Tests\Feature\Mobile;

use App\Models\MobileToken;

class MobileAuthTest extends MobileTestCase
{
    public function test_login_success_returns_token_customer_and_account(): void
    {
        $response = $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'pin' => '123456',
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

    public function test_login_with_wrong_pin_returns_unauthorized(): void
    {
        $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'pin' => '999999',
        ])->assertStatus(401)
            ->assertJsonPath('success', false);

        $this->assertSame(1, $this->nasabah->fresh()->failed_pin_attempts);
    }

    public function test_login_with_unknown_student_number_returns_validation_error(): void
    {
        $this->postJson(route('api.mobile.login'), [
            'student_number' => '0000000',
            'pin' => '123456',
        ])->assertStatus(422);
    }

    public function test_login_rejects_blocked_account(): void
    {
        $this->nasabah->update(['status' => 'blocked']);

        $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'pin' => '123456',
        ])->assertStatus(403);
    }

    public function test_account_locks_after_three_failed_attempts(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->postJson(route('api.mobile.login'), [
                'student_number' => '2023001',
                'pin' => '999999',
            ])->assertStatus($attempt === 3 ? 423 : 401);
        }

        $fresh = $this->nasabah->fresh();
        $this->assertSame(3, $fresh->failed_pin_attempts);
        $this->assertTrue($fresh->isPinLocked());

        $this->postJson(route('api.mobile.login'), [
            'student_number' => '2023001',
            'pin' => '123456',
        ])->assertStatus(423);
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
