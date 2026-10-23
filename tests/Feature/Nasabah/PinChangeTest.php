<?php

namespace Tests\Feature\Nasabah;

class PinChangeTest extends NasabahTestCase
{
    public function test_pin_page_is_reachable_from_the_menu(): void
    {
        $this->actingAsNasabah()
            ->get(route('nasabah.pin'))
            ->assertOk()
            ->assertSee('Ubah PIN Transaksi')
            ->assertSee(route('nasabah.pin.update'));
    }

    public function test_nasabah_menu_links_to_the_pin_page(): void
    {
        $this->actingAsNasabah()
            ->get(route('nasabah.dashboard'))
            ->assertOk()
            ->assertSee(route('nasabah.pin'));
    }

    public function test_change_pin_updates_the_transaction_pin(): void
    {
        $this->actingAsNasabah()
            ->from(route('nasabah.pin'))
            ->put(route('nasabah.pin.update'), [
                'current_pin' => '123456',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])
            ->assertRedirect(route('nasabah.pin'))
            ->assertSessionHas('success');

        $fresh = $this->nasabah->fresh();

        $this->assertTrue(password_verify('654321', $fresh->pin_hash));
        $this->assertSame(0, $fresh->failed_pin_attempts);

        // Password login tidak ikut berubah.
        $this->assertTrue(password_verify('password123', $fresh->password_hash));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer_pin_changed',
            'entity_name' => 'saver',
            'entity_id' => $this->nasabah->id,
            'user_id' => null,
        ]);
    }

    public function test_change_pin_with_wrong_current_pin_shows_the_error(): void
    {
        $response = $this->actingAsNasabah()
            ->from(route('nasabah.pin'))
            ->put(route('nasabah.pin.update'), [
                'current_pin' => '000000',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ]);

        $response->assertRedirect(route('nasabah.pin'))
            ->assertSessionHas('error');

        $this->assertStringContainsString('PIN nasabah salah', (string) session('error'));

        $this->assertSame(1, $this->nasabah->refresh()->failed_pin_attempts);
        $this->assertTrue(password_verify('123456', $this->nasabah->fresh()->pin_hash));
    }

    public function test_three_wrong_current_pins_lock_the_flow(): void
    {
        foreach (['000000', '111111', '222222'] as $current) {
            $this->actingAsNasabah()
                ->from(route('nasabah.pin'))
                ->put(route('nasabah.pin.update'), [
                    'current_pin' => $current,
                    'pin' => '654321',
                    'pin_confirmation' => '654321',
                ])
                ->assertRedirect(route('nasabah.pin'))
                ->assertSessionHas('error');
        }

        $fresh = $this->nasabah->refresh();

        $this->assertTrue($fresh->isPinLocked());

        // Sudah terkunci: PIN benarpun ditolak sampai masa kunci selesai.
        $this->actingAsNasabah()
            ->from(route('nasabah.pin'))
            ->put(route('nasabah.pin.update'), [
                'current_pin' => '123456',
                'pin' => '999999',
                'pin_confirmation' => '999999',
            ])
            ->assertRedirect(route('nasabah.pin'))
            ->assertSessionHas('error');
    }

    public function test_change_pin_rejects_the_same_pin_as_current(): void
    {
        $this->actingAsNasabah()
            ->from(route('nasabah.pin'))
            ->put(route('nasabah.pin.update'), [
                'current_pin' => '123456',
                'pin' => '123456',
                'pin_confirmation' => '123456',
            ])
            ->assertSessionHasErrors('pin');

        $this->assertTrue(password_verify('123456', $this->nasabah->fresh()->pin_hash));
    }

    public function test_change_pin_requires_confirmation(): void
    {
        $this->actingAsNasabah()
            ->from(route('nasabah.pin'))
            ->put(route('nasabah.pin.update'), [
                'current_pin' => '123456',
                'pin' => '654321',
                'pin_confirmation' => '111111',
            ])
            ->assertSessionHasErrors('pin');

        $this->assertTrue(password_verify('123456', $this->nasabah->fresh()->pin_hash));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('nasabah.pin'))->assertRedirect(route('login'));
        $this->put(route('nasabah.pin.update'))->assertRedirect(route('login'));
    }
}
