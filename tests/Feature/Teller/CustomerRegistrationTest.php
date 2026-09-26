<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Member;
use App\Models\Saver;

class CustomerRegistrationTest extends TellerTestCase
{
    public function test_teller_can_open_registration_form(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.members.register'))
            ->assertOk()
            ->assertSee('Registrasi Anggota');
    }

    public function test_teller_registers_member_without_account(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.members.register.store'), [
                'full_name' => 'Budi Santoso',
                'graduation_year' => '2026',
            ])
            ->assertRedirect(route('teller.members.register'))
            ->assertSessionHas('success');

        $this->assertSame(1, Member::count());
        $this->assertSame(1, Saver::count());
        $this->assertSame(1, Account::count());

        $member = Member::where('full_name', 'Budi Santoso')->firstOrFail();
        $this->assertNotNull($member->nis);
        $this->assertSame(Member::STATUS_ACCOUNT_INACTIVE, $member->status);

        $this->assertDatabaseHas('audit_logs', ['action' => 'member_registered']);
    }

    public function test_registration_requires_full_name(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.members.register.store'), [])
            ->assertSessionHasErrors('full_name');

        $this->assertSame(0, Member::count());
    }

    public function test_admin_cannot_use_teller_registration(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('teller.members.register.store'), [
                'full_name' => 'Budi Santoso',
            ])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Member::count());
    }
}
