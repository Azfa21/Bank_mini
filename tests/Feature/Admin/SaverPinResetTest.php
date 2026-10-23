<?php

namespace Tests\Feature\Admin;

use Tests\Feature\Teller\TellerTestCase;

/**
 * Administrator memegang jalur reset PIN transaksi. Password login penabung
 * tidak ikut berubah: PIN dan password adalah dua kredensial terpisah.
 */
class SaverPinResetTest extends TellerTestCase
{
    public function test_admin_can_reset_the_transaction_pin_without_touching_the_password(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put(route('admin.savers.update', $this->nasabah), [
                'pin' => '998877',
                'reset_reason' => 'Siswa lupa PIN dan membawa kartu rekening.',
            ])
            ->assertRedirect(route('admin.savers.index'));

        $fresh = $this->nasabah->fresh();

        $this->assertTrue(password_verify('998877', $fresh->pin_hash));
        $this->assertTrue(password_verify('password123', $fresh->password_hash));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pin_reset_by_admin',
            'entity_name' => 'saver',
            'entity_id' => $this->nasabah->id,
        ]);
    }

    public function test_admin_pin_reset_requires_a_reason(): void
    {
        $this->actingAs($this->admin, 'web')
            ->from(route('admin.savers.edit', $this->nasabah))
            ->put(route('admin.savers.update', $this->nasabah), [
                'pin' => '998877',
            ])
            ->assertSessionHasErrors('reset_reason');

        $this->assertTrue(password_verify('123456', $this->nasabah->fresh()->pin_hash));
    }

    public function test_admin_can_update_the_profile_without_touching_the_pin(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put(route('admin.savers.update', $this->nasabah), [
                'full_name' => 'Ani Wijaya Putri',
            ])
            ->assertRedirect(route('admin.savers.index'));

        $fresh = $this->nasabah->fresh();

        $this->assertSame('Ani Wijaya Putri', $fresh->full_name);
        $this->assertTrue(password_verify('123456', $fresh->pin_hash));
    }

    public function test_edit_page_labels_the_pin_as_a_transaction_credential(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.savers.edit', $this->nasabah))
            ->assertOk()
            ->assertSee('PIN Transaksi Baru')
            ->assertSee('Password login penabung tidak terpengaruh.');
    }
}
