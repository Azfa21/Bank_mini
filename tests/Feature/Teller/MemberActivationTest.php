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
                'physical_cash_amount' => 50000,
                'pin' => '654321',
                'class_name' => 'VII A',
                'email' => 'budi.santoso@example.test',
            ])
            ->assertRedirect();

        $saver = Saver::where('member_nis', '20260001')->firstOrFail();

        $this->assertSame('active', $saver->status);
        $this->assertNotNull($saver->account);
        $this->assertSame(50000, $saver->account->balance);
        $this->assertSame(Member::STATUS_ACCOUNT_ACTIVE, $member->fresh()->status);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $saver->account->id,
            'type' => 'deposit',
            'amount' => 50000,
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'member_activated']);
    }

    public function test_activation_rejects_deposit_below_threshold(): void
    {
        $member = $this->makeMember();

        $this->actingAsTeller()
            ->from(route('teller.members.activate', $member))
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 5000,
                'physical_cash_amount' => 5000,
                'pin' => '654321',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Saver::where('member_nis', '20260001')->count());
        $this->assertSame(Member::STATUS_ACCOUNT_INACTIVE, $member->fresh()->status);
    }

    public function test_activation_rejects_mismatched_physical_cash(): void
    {
        $member = $this->makeMember();

        $this->actingAsTeller()
            ->from(route('teller.members.activate', $member))
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 50000,
                'physical_cash_amount' => 40000,
                'pin' => '654321',
            ])
            ->assertSessionHasErrors('physical_cash_amount');

        $this->assertSame(0, Saver::where('member_nis', '20260001')->count());
    }

    public function test_activation_rejects_member_that_is_already_active(): void
    {
        $member = $this->makeMember();
        $member->update(['status' => Member::STATUS_ACCOUNT_ACTIVE]);

        $this->actingAsTeller()
            ->from(route('teller.members.activate', $member))
            ->post(route('teller.members.activate.store', $member), [
                'amount' => 50000,
                'physical_cash_amount' => 50000,
                'pin' => '654321',
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
                'physical_cash_amount' => 50000,
                'pin' => '654321',
            ])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Saver::where('member_nis', '20260001')->count());
    }
}
