<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser('admin', 'admin');
    }

    private function createUser(string $username, string $role, string $status = 'active'): User
    {
        return User::create([
            'username' => $username,
            'password_hash' => Hash::make('password'),
            'full_name' => ucfirst($username),
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function test_admin_cannot_delete_last_active_supervisor(): void
    {
        $supervisor = $this->createUser('supervisor1', 'supervisor');

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $supervisor))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Penghapusan akun ditolak karena minimal harus tersisa 1 Supervisor aktif.');

        $this->assertDatabaseHas('users', ['id' => $supervisor->id]);
    }

    public function test_admin_cannot_delete_last_active_teller(): void
    {
        $teller = $this->createUser('teller1', 'teller');

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $teller))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Penghapusan akun ditolak karena minimal harus tersisa 1 Teller aktif.');

        $this->assertDatabaseHas('users', ['id' => $teller->id]);
    }

    public function test_admin_can_delete_user_when_another_active_member_of_same_role_exists(): void
    {
        $teller1 = $this->createUser('teller1', 'teller');
        $teller2 = $this->createUser('teller2', 'teller');

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $teller1))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $teller1->id]);
        $this->assertDatabaseHas('users', ['id' => $teller2->id]);
    }

    public function test_admin_can_delete_blocked_user_when_no_other_active_member_of_role_exists(): void
    {
        $supervisor = $this->createUser('supervisor1', 'supervisor', 'blocked');

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $supervisor))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $supervisor->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $otherAdmin = $this->createUser('admin2', 'admin');

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $this->admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Anda tidak dapat menghapus akun Anda sendiri.');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_api_rejects_deleting_last_active_role_member_with_422(): void
    {
        $supervisor = $this->createUser('supervisor1', 'supervisor');

        $this->actingAs($this->admin, 'web')
            ->deleteJson("/api/admin/users/{$supervisor->id}")
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Penghapusan akun ditolak karena minimal harus tersisa 1 Supervisor aktif.',
            ]);

        $this->assertDatabaseHas('users', ['id' => $supervisor->id]);
    }

    public function test_admin_cannot_block_last_active_user_of_role_via_toggle_status(): void
    {
        $supervisor = $this->createUser('supervisor1', 'supervisor');
        $teller = $this->createUser('teller1', 'teller');

        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.users.toggle-status', $supervisor))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Pemblokiran akun ditolak karena minimal harus tersisa 1 Supervisor aktif.');

        $this->assertDatabaseHas('users', ['id' => $supervisor->id, 'status' => 'active']);

        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.users.toggle-status', $teller))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Pemblokiran akun ditolak karena minimal harus tersisa 1 Teller aktif.');

        $this->assertDatabaseHas('users', ['id' => $teller->id, 'status' => 'active']);
    }

    public function test_admin_can_block_and_unblock_non_last_user_via_toggle_status(): void
    {
        $teller1 = $this->createUser('teller1', 'teller');
        $teller2 = $this->createUser('teller2', 'teller');

        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.users.toggle-status', $teller1))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $teller1->id, 'status' => 'blocked']);

        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.users.toggle-status', $teller1))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $teller1->id, 'status' => 'active']);
        $this->assertDatabaseHas('users', ['id' => $teller2->id, 'status' => 'active']);
    }

    public function test_update_cannot_demote_or_block_last_active_member_of_role(): void
    {
        $supervisor = $this->createUser('supervisor1', 'supervisor');
        $teller = $this->createUser('teller1', 'teller');

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.users.update', $supervisor), [
                'full_name' => $supervisor->full_name,
                'username' => $supervisor->username,
                'role' => 'teller',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Perubahan akun ditolak karena minimal harus tersisa 1 Supervisor aktif.');

        $this->assertDatabaseHas('users', ['id' => $supervisor->id, 'role' => 'supervisor']);

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.users.update', $teller), [
                'full_name' => $teller->full_name,
                'username' => $teller->username,
                'role' => 'teller',
                'status' => 'blocked',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Perubahan akun ditolak karena minimal harus tersisa 1 Teller aktif.');

        $this->assertDatabaseHas('users', ['id' => $teller->id, 'status' => 'active']);
    }

    public function test_update_allowed_when_minimum_active_members_preserved(): void
    {
        $supervisor1 = $this->createUser('supervisor1', 'supervisor');
        $supervisor2 = $this->createUser('supervisor2', 'supervisor');

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.users.update', $supervisor1), [
                'full_name' => $supervisor1->full_name,
                'username' => $supervisor1->username,
                'role' => 'teller',
                'status' => 'blocked',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $supervisor1->id, 'role' => 'teller', 'status' => 'blocked']);
        $this->assertDatabaseHas('users', ['id' => $supervisor2->id, 'role' => 'supervisor', 'status' => 'active']);
    }

    public function test_delete_fails_gracefully_when_user_has_related_data(): void
    {
        $teller1 = $this->createUser('teller1', 'teller');
        $teller2 = $this->createUser('teller2', 'teller');

        Customer::create([
            'student_number' => '2023001',
            'full_name' => 'Ani Wijaya',
            'class_name' => 'X RPL 1',
            'phone_number' => '081234567001',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-1',
            'created_by' => $teller2->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $teller2))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas(
                'error',
                'Akun pengguna tidak dapat dihapus karena masih memiliki data terkait (nasabah/transaksi/laporan).'
            );

        $this->assertDatabaseHas('users', ['id' => $teller2->id]);
        $this->assertDatabaseHas('users', ['id' => $teller1->id]);
    }
}
