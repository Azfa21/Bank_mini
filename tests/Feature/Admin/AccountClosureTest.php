<?php

namespace Tests\Feature\Admin;

use App\Models\AccountClosure;
use App\Models\Member;
use App\Models\MobileToken;
use App\Models\Transaction;
use Tests\Feature\Teller\TellerTestCase;

/**
 * Penutupan rekening alumni: Teller mencairkan saldo di atas saldo mengendap di loket
 * (saldo mengendap tetap tersimpan), Administrator menyetujui (rekening ditutup, penabung
 * jadi alumni) atau menolak (pencairan dibatalkan dan saldo utuh kembali).
 */
class AccountClosureTest extends TellerTestCase
{
    public function test_teller_can_request_closure_and_payout_keeps_minimum_balance(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $this->account), [
                'pin' => '123456',
                'notes' => 'Lulus tahun 2026',
            ])
            ->assertRedirect(route('teller.members.show', $this->account))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('account_closures', [
            'account_id' => $this->account->id,
            'status' => 'pending',
            'payout_amount' => 15000,
        ]);

        // Saldo mengendap Rp 10.000 tidak bisa dicairkan dan tetap tersimpan.
        $this->assertSame(10000, $this->account->refresh()->balance);

        $payout = Transaction::query()
            ->where('account_id', $this->account->id)
            ->where('type', 'withdrawal')
            ->firstOrFail();

        $this->assertSame(15000, $payout->amount);
        $this->assertSame(10000, $payout->balance_after);
        $this->assertSame(2, $payout->journalEntries()->count());
    }

    public function test_closure_payout_uses_the_stricter_account_minimum_balance(): void
    {
        $this->account->update(['minimum_balance' => 20000]);

        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $this->account), ['pin' => '123456']);

        // Batas rekening Rp 20.000 lebih ketat daripada pengaturan global Rp 10.000.
        $this->assertSame(20000, $this->account->refresh()->balance);
        $this->assertDatabaseHas('account_closures', [
            'account_id' => $this->account->id,
            'status' => 'pending',
            'payout_amount' => 5000,
        ]);
    }

    public function test_closure_request_requires_the_correct_pin(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.accounts.closure.create', $this->account))
            ->post(route('teller.accounts.closure.store', $this->account), ['pin' => '999999'])
            ->assertSessionHas('error');

        $this->assertSame(0, AccountClosure::count());
        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_pending_closure_blocks_new_transactions(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $this->account), ['pin' => '123456']);

        $this->actingAsTeller()
            ->from(route('teller.deposits.create', $this->account))
            ->post(route('teller.deposits.store', $this->account), [
                'slip_number' => 'SLIP-CLS-001',
                'slip_amount' => 50000,
            ])
            ->assertSessionHas('error');

        // Hanya transaksi pencairan penutupan yang tersimpan.
        $this->assertSame(1, Transaction::count());
    }

    public function test_admin_approval_closes_account_and_graduates_saver(): void
    {
        $member = $this->createMemberFor($this->nasabah->student_number);

        $token = MobileToken::create([
            'saver_id' => $this->nasabah->id,
            'name' => 'android',
            'token_hash' => hash('sha256', 'contoh-token'),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $this->account), ['pin' => '123456']);

        $closure = AccountClosure::firstOrFail();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.closures.approve', $closure), ['decision_notes' => 'Data sesuai'])
            ->assertRedirect(route('admin.graduations.index', ['year' => '2026']));

        $this->assertDatabaseHas('accounts', ['id' => $this->account->id, 'status' => 'closed']);
        $this->assertNotNull($this->account->refresh()->closed_at);

        $this->assertDatabaseHas('savers', ['id' => $this->nasabah->id, 'status' => 'graduated']);
        $this->assertNotNull($this->nasabah->refresh()->graduated_at);
        $this->assertNotNull($member->refresh()->graduated_at);

        $this->assertDatabaseHas('account_closures', [
            'id' => $closure->id,
            'status' => 'approved',
            'decided_by' => $this->admin->id,
        ]);

        // Sesi aplikasi nasabah ikut diputus.
        $this->assertDatabaseMissing('mobile_tokens', ['id' => $token->id]);
    }

    public function test_admin_rejection_returns_balance_and_keeps_saver_active(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $this->account), ['pin' => '123456']);

        $closure = AccountClosure::firstOrFail();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.closures.reject', $closure), ['rejection_reason' => 'Siswa belum lulus'])
            ->assertRedirect(route('admin.graduations.index'));

        $this->assertDatabaseHas('account_closures', ['id' => $closure->id, 'status' => 'rejected']);
        $this->assertSame(25000, $this->account->refresh()->balance);
        $this->assertDatabaseHas('savers', ['id' => $this->nasabah->id, 'status' => 'active']);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->account->id,
            'type' => 'reversal',
        ]);
    }

    public function test_graduated_saver_cannot_login_on_web_or_mobile(): void
    {
        $this->nasabah->update(['status' => 'graduated']);

        $this->post(route('login.submit'), [
            'identifier' => $this->nasabah->student_number,
            'secret' => 'password123',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest('nasabah');

        $this->postJson(route('api.mobile.login'), [
            'student_number' => $this->nasabah->student_number,
            'password' => 'password123',
        ])->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_teller_cannot_open_admin_graduation_pages(): void
    {
        $this->actingAsTeller()->getJson('/api/admin/graduations')->assertForbidden();
    }

    public function test_zero_balance_closure_skips_payout_and_can_be_approved(): void
    {
        $this->account->update(['balance' => 0]);

        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $this->account), ['pin' => '123456'])
            ->assertRedirect(route('teller.members.show', $this->account));

        $this->assertDatabaseHas('account_closures', [
            'account_id' => $this->account->id,
            'status' => 'pending',
            'payout_amount' => 0,
        ]);
        $this->assertSame(0, Transaction::count());

        $closure = AccountClosure::firstOrFail();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.closures.approve', $closure))
            ->assertRedirect(route('admin.graduations.index'));

        $this->assertDatabaseHas('accounts', ['id' => $this->account->id, 'status' => 'closed']);
        $this->assertDatabaseHas('savers', ['id' => $this->nasabah->id, 'status' => 'graduated']);
    }

    public function test_closure_with_only_minimum_balance_skips_payout_and_can_be_approved(): void
    {
        $this->account->update(['balance' => 10000]);

        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $this->account), ['pin' => '123456'])
            ->assertRedirect(route('teller.members.show', $this->account));

        // Saldo sudah habis terisi saldo mengendap, jadi tidak ada yang dicairkan.
        $this->assertDatabaseHas('account_closures', [
            'account_id' => $this->account->id,
            'status' => 'pending',
            'payout_amount' => 0,
        ]);
        $this->assertSame(10000, $this->account->refresh()->balance);
        $this->assertSame(0, Transaction::count());

        $closure = AccountClosure::firstOrFail();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.closures.approve', $closure))
            ->assertRedirect(route('admin.graduations.index'));

        $this->assertDatabaseHas('accounts', ['id' => $this->account->id, 'status' => 'closed']);
    }

    public function test_graduated_saver_is_hidden_from_default_teller_search(): void
    {
        $this->nasabah->update(['status' => 'graduated']);

        $this->actingAsTeller()
            ->get(route('teller.members.lookup', ['q' => 'Ani Wijaya']))
            ->assertSessionHas('error');

        $this->actingAsTeller()
            ->getJson(route('teller.members.lookup', ['q' => 'Ani Wijaya']))
            ->assertStatus(404);
    }

    public function test_graduation_page_shows_pending_closure_for_admin(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.accounts.closure.create', $this->account))
            ->assertOk()
            ->assertSee($this->nasabah->full_name);

        $this->createMemberFor($this->nasabah->student_number);

        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $this->account), ['pin' => '123456']);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.graduations.index', ['year' => '2026']))
            ->assertOk()
            ->assertSee($this->nasabah->full_name)
            ->assertSee('Menunggu keputusan')
            ->assertSee('Cair Rp 15.000');
    }

    public function test_closure_without_cohort_is_decided_from_graduation_page(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $this->account), ['pin' => '123456']);

        $closure = AccountClosure::firstOrFail();

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.graduations.index'))
            ->assertOk()
            ->assertSee('Penutupan menunggu keputusan tanpa angkatan')
            ->assertSee($this->nasabah->full_name);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.closures.approve', $closure))
            ->assertRedirect(route('admin.graduations.index'));

        $this->assertDatabaseHas('accounts', ['id' => $this->account->id, 'status' => 'closed']);
    }

    private function createMemberFor(string $nis): Member
    {
        $member = Member::create([
            'nis' => $nis,
            'full_name' => $this->nasabah->full_name,
            'graduation_year' => '2026',
            'status' => Member::STATUS_ACCOUNT_ACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->nasabah->update(['member_nis' => $nis]);

        return $member;
    }
}
