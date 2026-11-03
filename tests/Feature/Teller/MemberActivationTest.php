<?php

namespace Tests\Feature\Teller;

use App\Models\Member;
use App\Models\Saver;

class MemberActivationTest extends TellerTestCase
{
    private function makeMember(string $nis = '20260001', string $name = 'Budi Santoso'): Member
    {
        return Member::create([
            'nis' => $nis,
            'full_name' => $name,
            'graduation_year' => '2026',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_activation_creates_saver_account_and_deposit(): void
    {
        $member = $this->makeMember();

        $this->actingAsTeller()
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 50000,
                'pin' => '654321',
                'password' => 'password-awal-123',
                'password_confirmation' => 'password-awal-123',
                'class_name' => 'VII A',
                'email' => 'budi.santoso@example.test',
            ])
            ->assertRedirect();

        $saver = Saver::where('member_nis', '20260001')->firstOrFail();

        $this->assertSame('active', $saver->status);
        $this->assertNotNull($saver->account);
        $this->assertSame(50000, $saver->account->balance);
        $this->assertSame(Member::STATUS_ACCOUNT_ACTIVE, $member->fresh()->status);
        $this->assertTrue(password_verify('password-awal-123', $saver->password_hash));
        $this->assertTrue(password_verify('654321', $saver->pin_hash));

        $this->assertDatabaseHas('transactions', [
            'account_id' => $saver->account->id,
            'type' => 'deposit',
            'amount' => 50000,
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'member_activated']);
    }

    public function test_activation_form_prefills_class_from_member_data(): void
    {
        $member = $this->makeMember();
        $member->update(['class_name' => 'X RPL 1']);

        $this->actingAsTeller()
            ->get(route('teller.members.activate', $member))
            ->assertOk()
            ->assertSee('Terisi dari data anggota. Ubah kalau sudah naik kelas.')
            ->assertSee('value="X RPL 1"', false);
    }

    public function test_activation_uses_the_class_submitted_by_the_teller(): void
    {
        $member = $this->makeMember();
        $member->update(['class_name' => 'X RPL 1']);

        $this->actingAsTeller()
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 50000,
                'pin' => '654321',
                'password' => 'password-awal-123',
                'password_confirmation' => 'password-awal-123',
                'class_name' => 'X RPL 2',
                'email' => 'budi.santoso@example.test',
            ])
            ->assertRedirect();

        $this->assertSame('X RPL 2', Saver::where('member_nis', '20260001')->firstOrFail()->class_name);
    }

    public function test_activation_rejects_deposit_below_threshold(): void
    {
        $member = $this->makeMember();

        $this->actingAsTeller()
            ->from(route('teller.members.activate', $member))
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 5000,
                'pin' => '654321',
                'password' => 'password-awal-123',
                'password_confirmation' => 'password-awal-123',
                'email' => 'wali.test@smp.test',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Saver::where('member_nis', '20260001')->count());
        $this->assertSame(Member::STATUS_ACCOUNT_INACTIVE, $member->fresh()->status);
    }

    public function test_activation_rejects_member_that_is_already_active(): void
    {
        $member = $this->makeMember();
        $member->update(['status' => Member::STATUS_ACCOUNT_ACTIVE]);

        $this->actingAsTeller()
            ->from(route('teller.members.activate', $member))
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 50000,
                'pin' => '654321',
                'password' => 'password-awal-123',
                'password_confirmation' => 'password-awal-123',
                'email' => 'wali.test@smp.test',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Saver::where('member_nis', '20260001')->count());
    }

    public function test_admin_cannot_activate_member(): void
    {
        $member = $this->makeMember();

        $this->actingAs($this->admin, 'web')
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 50000,
                'pin' => '654321',
                'password' => 'password-awal-123',
                'password_confirmation' => 'password-awal-123',
                'email' => 'wali.test@smp.test',
            ])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Saver::where('member_nis', '20260001')->count());
    }

    public function test_activation_form_asks_the_saver_to_create_a_login_password(): void
    {
        $member = $this->makeMember();

        $this->actingAsTeller()
            ->get(route('teller.members.activate', $member))
            ->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Password aplikasi penabung wajib');
    }

    public function test_activation_rejects_a_password_shorter_than_eight_characters(): void
    {
        $member = $this->makeMember();

        $this->actingAsTeller()
            ->from(route('teller.members.activate', $member))
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 50000,
                'pin' => '654321',
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
                'email' => 'wali.test@smp.test',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame(0, Saver::where('member_nis', '20260001')->count());
    }

    public function test_activation_rejects_mismatched_password_confirmation(): void
    {
        $member = $this->makeMember();

        $this->actingAsTeller()
            ->from(route('teller.members.activate', $member))
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 50000,
                'pin' => '654321',
                'password' => 'password-awal-123',
                'password_confirmation' => 'password-lain-456',
                'email' => 'wali.test@smp.test',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame(0, Saver::where('member_nis', '20260001')->count());
    }

    /**
     * Setelah aktivasi, penabung masuk dengan password buatannya sendiri.
     * PIN transaksi sengaja ditolak di gerbang login.
     */
    public function test_saver_can_log_in_with_the_password_but_not_the_transaction_pin(): void
    {
        $member = $this->makeMember();

        $this->actingAsTeller()
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 50000,
                'pin' => '654321',
                'password' => 'password-awal-123',
                'password_confirmation' => 'password-awal-123',
                'email' => 'budi.santoso@example.test',
            ])
            ->assertRedirect();

        $this->post('/login', [
            'identifier' => '20260001',
            'secret' => '654321',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest('nasabah');

        $this->post('/login', [
            'identifier' => '20260001',
            'secret' => 'password-awal-123',
        ])->assertSessionHasErrors(['identifier' => 'Akun penabung hanya dapat diakses melalui aplikasi mobile Bank Mini.']);

        $this->assertGuest('nasabah');

        $this->postJson(route('api.mobile.login'), [
            'student_number' => '20260001',
            'password' => 'password-awal-123',
        ])->assertOk();
    }
}
