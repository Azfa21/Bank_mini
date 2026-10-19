<?php

namespace Tests\Feature\Admin;

use App\Models\AccountClosure;
use App\Models\Member;
use App\Models\MobileToken;
use App\Models\Transaction;
use Tests\Feature\Teller\TellerTestCase;

/**
 * Penutupan rekening alumni: Teller mencairkan seluruh saldo di loket, Administrator
 * menyetujui (rekening ditutup, penabung jadi alumni) atau menolak (saldo dikembalikan).
 */
class AccountClosureTest extends TellerTestCase
{
    public function test_teller_can_request_closure_and_full_balance_is_paid_out(): void
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
            'payout_amount' => 25000,
        ]);

        // Saldo mengendap Rp 10.000 ikut dicairkan karena rekening berhenti.
        $this->assertSame(0, $this->account->refresh()->balance);

        $payout = Transaction::query()
            ->where('account_id', $this->account->id)
            ->where('type', 'withdrawal')
            ->firstOrFail();

        $this->assertSame(25000, $payout->amount);
        $this->assertSame(0, $payout->balance_after);
        $this->assertSame(2, $payout->journalEntries()->count());
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
            ->post(route('admin.closures.approve', $closure), ['decision_notes' => 'Data sesuai'])
            ->assertRedirect(route('admin.closures.show', $closure));

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
            ->post(route('admin.closures.reject', $closure), ['rejection_reason' => 'Siswa belum lulus'])
            ->assertRedirect(route('admin.closures.show', $closure));

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
            'secret' => '123456',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest('nasabah');

        $this->postJson(route('api.mobile.login'), [
            'student_number' => $this->nasabah->student_number,
            'pin' => '123456',
        ])->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_teller_cannot_open_admin_closure_pages(): void
    {
        $this->actingAsTeller()->getJson('/api/admin/closures')->assertForbidden();
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
            ->post(route('admin.closures.approve', $closure))
            ->assertRedirect(route('admin.closures.show', $closure));

        $this->assertDatabaseHas('accounts', ['id' => $this->account->id, 'status' => 'closed']);
        $this->assertDatabaseHas('savers', ['id' => $this->nasabah->id, 'status' => 'graduated']);
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

    public function test_closure_pages_render_for_admin_and_teller(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.closures.index'))
            ->assertOk()
            ->assertSee('Penutupan rekening alumni');

        $this->actingAsTeller()
            ->get(route('teller.accounts.closure.create', $this->account))
            ->assertOk()
            ->assertSee($this->nasabah->full_name);
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
