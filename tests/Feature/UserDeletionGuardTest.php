<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Member;
use App\Models\Saver;
use App\Models\Transaction;
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

    public function test_admin_can_delete_another_active_admin(): void
    {
        $admin2 = $this->createUser('admin2', 'admin');

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $admin2))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);
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
        $teller = $this->createUser('teller1', 'teller', 'blocked');

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $teller))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $teller->id]);
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
        $teller = $this->createUser('teller1', 'teller');

        $this->actingAs($this->admin, 'web')
            ->deleteJson("/api/admin/users/{$teller->id}")
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Penghapusan akun ditolak karena minimal harus tersisa 1 Teller aktif.',
            ]);

        $this->assertDatabaseHas('users', ['id' => $teller->id]);
    }

    public function test_admin_cannot_block_last_active_user_of_role_via_toggle_status(): void
    {
        $teller = $this->createUser('teller1', 'teller');

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

    public function test_update_cannot_demote_or_block_last_active_teller(): void
    {
        $teller = $this->createUser('teller1', 'teller');

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.users.update', $teller), [
                'full_name' => $teller->full_name,
                'username' => $teller->username,
                'role' => 'admin',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Perubahan akun ditolak karena minimal harus tersisa 1 Teller aktif.');

        $this->assertDatabaseHas('users', ['id' => $teller->id, 'role' => 'teller']);

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
        $admin2 = $this->createUser('admin2', 'admin');
        $admin3 = $this->createUser('admin3', 'admin');

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.users.update', $admin2), [
                'full_name' => $admin2->full_name,
                'username' => $admin2->username,
                'role' => 'teller',
                'status' => 'blocked',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $admin2->id, 'role' => 'teller', 'status' => 'blocked']);
        $this->assertDatabaseHas('users', ['id' => $admin3->id, 'role' => 'admin', 'status' => 'active']);
    }

    public function test_delete_fails_gracefully_when_user_has_related_data(): void
    {
        $teller1 = $this->createUser('teller1', 'teller');
        $teller2 = $this->createUser('teller2', 'teller');

        Saver::create([
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
                'Akun pengguna tidak dapat dihapus karena masih memiliki data terkait (anggota/nasabah/transaksi/laporan).'
            );

        $this->assertDatabaseHas('users', ['id' => $teller2->id]);
        $this->assertDatabaseHas('users', ['id' => $teller1->id]);
    }

    public function test_admin_cannot_delete_user_who_registered_member(): void
    {
        $teller1 = $this->createUser('teller1', 'teller');
        $teller2 = $this->createUser('teller2', 'teller');

        Member::create([
            'nis' => '2023001',
            'full_name' => 'Ani Wijaya',
            'graduation_year' => '2026',
            'created_by' => $teller2->id,
        ]);

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $teller2))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas(
                'error',
                'Akun pengguna tidak dapat dihapus karena masih memiliki data terkait (anggota/nasabah/transaksi/laporan).'
            );

        $this->assertDatabaseHas('users', ['id' => $teller2->id]);
        $this->assertDatabaseHas('users', ['id' => $teller1->id]);
    }

    public function test_admin_cannot_delete_user_who_reversed_transaction(): void
    {
        $teller1 = $this->createUser('teller1', 'teller');
        $teller2 = $this->createUser('teller2', 'teller');

        $saver = Saver::create([
            'student_number' => '2023001',
            'full_name' => 'Ani Wijaya',
            'class_name' => 'X RPL 1',
            'phone_number' => '081234567001',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-1',
            'created_by' => $teller1->id,
            'status' => 'active',
        ]);

        $account = Account::create([
            'account_number' => '2026/2027-001',
            'saver_id' => $saver->id,
            'balance' => 100000,
            'opening_balance' => 100000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);

        Transaction::create([
            'account_id' => $account->id,
            'teller_id' => $teller1->id,
            'type' => 'deposit',
            'amount' => 100000,
            'balance_before' => 0,
            'balance_after' => 100000,
            'receipt_number' => 'TRX-REV-0001',
            'slip_number' => 'SLIP-REV-001',
            'status' => 'reversed',
            'reversed_by' => $teller2->id,
            'reversed_at' => now(),
            'reversal_reason' => 'Salah input nominal',
            'transaction_at' => now(),
        ]);

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.users.destroy', $teller2))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas(
                'error',
                'Akun pengguna tidak dapat dihapus karena masih memiliki data terkait (anggota/nasabah/transaksi/laporan).'
            );

        $this->assertDatabaseHas('users', ['id' => $teller2->id]);
        $this->assertDatabaseHas('users', ['id' => $teller1->id]);
    }
}
