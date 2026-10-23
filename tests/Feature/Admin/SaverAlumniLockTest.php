<?php

namespace Tests\Feature\Admin;

use Tests\Feature\Teller\TellerTestCase;

/**
 * Data penabung alumni adalah arsip: halaman Penabung tidak boleh lagi
 * mengubah profil, PIN, atau statusnya, termasuk saat URL dipanggil langsung.
 */
class SaverAlumniLockTest extends TellerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->nasabah->update([
            'status' => 'graduated',
            'graduated_at' => now(),
        ]);
    }

    public function test_alumni_row_has_no_edit_link(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.savers.index'))
            ->assertOk()
            ->assertSee('Terkunci')
            ->assertDontSee(route('admin.savers.edit', $this->nasabah));
    }

    public function test_edit_page_is_closed_for_alumni(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.savers.edit', $this->nasabah))
            ->assertRedirect(route('admin.savers.index'))
            ->assertSessionHas('error');
    }

    public function test_alumni_profile_cannot_be_updated(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put(route('admin.savers.update', $this->nasabah), [
                'full_name' => 'Nama Yang Dipaksakan',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.savers.index'))
            ->assertSessionHas('error');

        $fresh = $this->nasabah->fresh();

        $this->assertSame('Ani Wijaya', $fresh->full_name);
        $this->assertSame('graduated', $fresh->status);
    }

    public function test_alumni_pin_cannot_be_reset(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put(route('admin.savers.update', $this->nasabah), [
                'pin' => '998877',
                'reset_reason' => 'Siswa lupa PIN.',
            ])
            ->assertRedirect(route('admin.savers.index'))
            ->assertSessionHas('error');

        $this->assertTrue(password_verify('123456', $this->nasabah->fresh()->pin_hash));
    }

    public function test_alumni_status_cannot_be_toggled(): void
    {
        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.savers.toggle-status', $this->nasabah))
            ->assertRedirect(route('admin.savers.index'))
            ->assertSessionHas('error');

        $this->assertSame('graduated', $this->nasabah->fresh()->status);
    }

    public function test_json_update_is_rejected_with_a_message(): void
    {
        $this->actingAs($this->admin, 'web')
            ->putJson(route('admin.savers.update', $this->nasabah), [
                'full_name' => 'Nama Yang Dipaksakan',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data Ani Wijaya tidak dapat diubah karena sudah menjadi alumni.',
            ]);
    }
}
