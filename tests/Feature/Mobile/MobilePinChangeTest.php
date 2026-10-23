<?php

namespace Tests\Feature\Mobile;

use App\Models\MobileToken;

class MobilePinChangeTest extends MobileTestCase
{
    public function test_change_pin_updates_the_transaction_pin(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->postJson(route('api.mobile.pin.change'), [
                'current_pin' => '123456',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(password_verify('654321', $this->nasabah->fresh()->pin_hash));
    }

    public function test_change_pin_keeps_the_login_session(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->postJson(route('api.mobile.pin.change'), [
                'current_pin' => '123456',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])->assertOk();

        // PIN bukan kredensial login, jadi menggantinya tidak boleh
        // mencabut token aplikasi yang sedang dipakai.
        $this->assertSame(1, MobileToken::query()->where('saver_id', $this->nasabah->id)->count());

        $this->withMobileToken($token)
            ->getJson(route('api.mobile.me'))
            ->assertOk();
    }

    public function test_change_pin_rejects_wrong_current_pin_and_counts_the_attempt(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->postJson(route('api.mobile.pin.change'), [
                'current_pin' => '000000',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(1, $this->nasabah->refresh()->failed_pin_attempts);
        $this->assertTrue(password_verify('123456', $this->nasabah->fresh()->pin_hash));
    }

    public function test_change_pin_rejects_the_same_pin_as_current(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->postJson(route('api.mobile.pin.change'), [
                'current_pin' => '123456',
                'pin' => '123456',
                'pin_confirmation' => '123456',
            ])
            ->assertStatus(422);
    }

    public function test_change_pin_requires_confirmation_to_match(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->postJson(route('api.mobile.pin.change'), [
                'current_pin' => '123456',
                'pin' => '654321',
                'pin_confirmation' => '111111',
            ])
            ->assertStatus(422);

        $this->assertTrue(password_verify('123456', $this->nasabah->fresh()->pin_hash));
    }

    public function test_change_pin_requires_a_token(): void
    {
        $this->postJson(route('api.mobile.pin.change'), [
            'current_pin' => '123456',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])->assertStatus(401);
    }

    public function test_changed_pin_is_accepted_by_the_teller_flow(): void
    {
        $token = $this->loginAndGetToken();

        $this->withMobileToken($token)
            ->postJson(route('api.mobile.pin.change'), [
                'current_pin' => '123456',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])->assertOk();

        // PIN baru yang sama dipakai penarikan di loket.
        // Middleware mobile menaruh user di Auth::guard('nasabah') dan ia
        // menempel sampai test berakhir. EnsureRole membaca guard nasabah
        // lebih dulu, jadi user sisa itu harus dilepas agar penarikan
        // dievaluasi sebagai aksi teller.
        $this->app['auth']->guard('nasabah')->forgetUser();

        $this->actingAsTeller()
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-PIN-990',
                'amount' => 5000,
                'pin' => '654321',
            ])
            ->assertRedirect();

        $this->assertSame(20000, $this->account->refresh()->balance);
    }
}
