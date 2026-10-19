<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Member;
use App\Models\Saver;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Teller\TellerTestCase;

/**
 * Arsip kelulusan per angkatan. Hanya anggota tanpa rekening dan penabung bersaldo nol
 * yang diproses; penabung yang masih bersaldo menunggu penyelesaian di loket Teller.
 */
class GraduationTest extends TellerTestCase
{
    public function test_batch_graduation_archives_roster_and_zero_balance_only(): void
    {
        $roster = $this->member('2026001', 'Rina Roster');

        [$zeroMember, $zeroSaver, $zeroAccount] = $this->saverWithAccount('2026002', 'Budi Nol', 0);
        [$paidMember, $paidSaver, $paidAccount] = $this->saverWithAccount('2026003', 'Citra Saldo', 25000);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.store'), ['graduation_year' => '2026'])
            ->assertRedirect(route('admin.graduations.index', ['year' => '2026']))
            ->assertSessionHas('success');

        $this->assertNotNull($roster->refresh()->graduated_at);

        $this->assertDatabaseHas('savers', ['id' => $zeroSaver->id, 'status' => 'graduated']);
        $this->assertDatabaseHas('accounts', ['id' => $zeroAccount->id, 'status' => 'closed']);
        $this->assertNotNull($zeroMember->refresh()->graduated_at);

        $this->assertDatabaseHas('savers', ['id' => $paidSaver->id, 'status' => 'active']);
        $this->assertDatabaseHas('accounts', ['id' => $paidAccount->id, 'status' => 'active']);
        $this->assertNull($paidMember->refresh()->graduated_at);
    }

    public function test_graduation_page_lists_students_with_their_state(): void
    {
        $this->member('2026001', 'Rina Roster');
        $this->saverWithAccount('2026002', 'Budi Nol', 0);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.graduations.index', ['year' => '2026']))
            ->assertOk()
            ->assertSee('Rina Roster')
            ->assertSee('Budi Nol')
            ->assertSee('Siap diarsipkan');
    }

    public function test_graduate_command_dry_run_does_not_change_data(): void
    {
        $roster = $this->member('2026001', 'Rina Roster');

        $this->artisan('bankmini:graduate', ['--year' => '2026', '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertNull($roster->refresh()->graduated_at);
    }

    public function test_graduate_command_archives_after_confirmation(): void
    {
        $roster = $this->member('2026001', 'Rina Roster');

        $this->artisan('bankmini:graduate', ['--year' => '2026'])
            ->expectsConfirmation('Proses kelulusan angkatan 2026 sekarang?', 'yes')
            ->assertExitCode(0);

        $this->assertNotNull($roster->refresh()->graduated_at);
    }

    public function test_admin_can_graduate_a_single_roster_member(): void
    {
        $roster = $this->member('2026001', 'Rina Roster');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.member', $roster))
            ->assertRedirect(route('admin.graduations.index', ['year' => '2026']))
            ->assertSessionHas('success');

        $this->assertNotNull($roster->refresh()->graduated_at);
    }

    public function test_single_graduation_refuses_member_with_remaining_balance(): void
    {
        [$paidMember] = $this->saverWithAccount('2026003', 'Citra Saldo', 25000);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.member', $paidMember))
            ->assertSessionHas('error');

        $this->assertNull($paidMember->refresh()->graduated_at);
    }

    private function member(string $nis, string $name): Member
    {
        return Member::create([
            'nis' => $nis,
            'full_name' => $name,
            'graduation_year' => '2026',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * @return array{0: Member, 1: Saver, 2: Account}
     */
    private function saverWithAccount(string $nis, string $name, int $balance): array
    {
        $member = $this->member($nis, $name);

        $saver = Saver::create([
            'student_number' => $nis,
            'member_nis' => $nis,
            'full_name' => $name,
            'class_name' => 'XII RPL 1',
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-'.$nis,
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $member->update(['status' => Member::STATUS_ACCOUNT_ACTIVE]);

        $account = Account::create([
            'account_number' => '2026/2027-'.$nis,
            'saver_id' => $saver->id,
            'balance' => $balance,
            'opening_balance' => $balance,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);

        return [$member, $saver, $account];
    }
}
