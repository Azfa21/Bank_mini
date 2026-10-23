<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\AccountClosure;
use App\Models\Member;
use App\Models\Saver;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    public function test_graduation_page_surfaces_pending_closure_counts(): void
    {
        [, $saver, $account] = $this->saverWithAccount('2026002', 'Budi Nol', 10000);

        AccountClosure::create([
            'account_id' => $account->id,
            'saver_id' => $saver->id,
            'requested_by' => $this->teller->id,
            'payout_amount' => 0,
            'status' => AccountClosure::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.graduations.index'))
            ->assertOk()
            ->assertSee('1 penutupan rekening menunggu keputusan')
            ->assertSee('2026 · 1 menunggu keputusan');
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

    public function test_admin_can_mark_member_not_graduated_and_moves_to_next_year(): void
    {
        $member = $this->member('2026001', 'Rina Roster');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.not-graduate', $member), [
                'graduation_year' => '2026',
                'reason' => 'Nilai belum tuntas',
            ])
            ->assertRedirect(route('admin.graduations.index', ['year' => '2026']))
            ->assertSessionHas('success');

        $member->refresh();

        $this->assertNull($member->graduated_at);
        $this->assertSame('2027', $member->graduation_year);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_not_graduated',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_not_graduated_with_pending_closure_reverses_payout_and_keeps_account_active(): void
    {
        [$member, $saver, $account] = $this->saverWithAccount('2026003', 'Citra Saldo', 25000);

        $this->actingAsTeller()
            ->post(route('teller.accounts.closure.store', $account), ['pin' => '123456']);

        $closure = AccountClosure::firstOrFail();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.not-graduate', $member), ['graduation_year' => '2026'])
            ->assertRedirect(route('admin.graduations.index', ['year' => '2026']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('account_closures', ['id' => $closure->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('savers', ['id' => $saver->id, 'status' => 'active']);
        $this->assertSame(25000, $account->refresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'type' => 'reversal',
        ]);

        $member->refresh();

        $this->assertNull($member->graduated_at);
        $this->assertSame('2027', $member->graduation_year);
    }

    public function test_member_marked_not_graduated_is_skipped_by_batch(): void
    {
        $member = $this->member('2026001', 'Rina Roster');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.not-graduate', $member), ['graduation_year' => '2026'])
            ->assertSessionHas('success');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.store'), ['graduation_year' => '2026']);

        $member->refresh();

        $this->assertNull($member->graduated_at);
        $this->assertSame('2027', $member->graduation_year);
    }

    public function test_not_graduate_rejects_stale_cohort(): void
    {
        $member = $this->member('2026001', 'Rina Roster');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.not-graduate', $member), ['graduation_year' => '2025'])
            ->assertSessionHas('error');

        $this->assertSame('2026', $member->refresh()->graduation_year);
    }

    public function test_admin_downloads_cohort_alumni_csv_and_server_copy_is_saved(): void
    {
        Storage::fake('local');

        $this->member('2026001', 'Rina Roster');
        $this->saverWithAccount('2026002', 'Budi Nol', 0);
        $this->saverWithAccount('2026003', 'Citra Saldo', 25000);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.store'), ['graduation_year' => '2026']);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.graduations.export', ['year' => '2026']))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.graduations.index', ['year' => '2026']))
            ->assertOk()
            ->assertSee('Unduh CSV alumni angkatan ini');
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.graduations.index'))
            ->assertOk()
            ->assertSee('Unduh arsip CSV seluruh alumni');

        $files = Storage::disk('local')->files('backups/alumni');
        $this->assertCount(1, $files);
        $this->assertStringContainsString('arsip-alumni-angkatan-2026-', basename($files[0]));

        $csv = Storage::disk('local')->get($files[0]);

        $this->assertStringContainsString('nis,nama,angkatan_keluar,kelas,tanggal_lulus,nomor_rekening,status_rekening,saldo_akhir_rp,rekening_ditutup_pada,status_penabung', $csv);
        $this->assertStringContainsString('2026001,"Rina Roster",2026,', $csv);
        $this->assertStringContainsString('2026002,"Budi Nol",2026,"XII RPL 1",', $csv);
        $this->assertStringContainsString(',closed,0,', $csv);
        $this->assertStringContainsString(',graduated', $csv);
        $this->assertStringNotContainsString('Citra Saldo', $csv);

        $this->assertDatabaseHas('audit_logs', ['action' => 'alumni_archive_exported']);
    }

    public function test_alumni_export_without_year_covers_all_cohorts(): void
    {
        Storage::fake('local');

        $this->member('2026001', 'Rina Roster', '2026');
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.store'), ['graduation_year' => '2026']);

        $this->member('2025001', 'Dimas Lama', '2025');
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.store'), ['graduation_year' => '2025']);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.graduations.export'))
            ->assertOk()
            ->assertDownload();

        $files = Storage::disk('local')->files('backups/alumni');
        $csv = Storage::disk('local')->get((string) $files[0]);

        $this->assertStringContainsString('2026001,"Rina Roster",2026', $csv);
        $this->assertStringContainsString('2025001,"Dimas Lama",2025', $csv);
    }

    public function test_alumni_export_refuses_cohort_without_alumni(): void
    {
        $this->member('2026001', 'Rina Roster');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.graduations.export', ['year' => '2026']))
            ->assertRedirect(route('admin.graduations.index', ['year' => '2026']))
            ->assertSessionHas('error');
    }

    public function test_teller_cannot_download_alumni_archive(): void
    {
        $this->member('2026001', 'Rina Roster');
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.graduations.store'), ['graduation_year' => '2026']);

        $this->actingAs($this->teller, 'web')
            ->get(route('admin.graduations.export', ['year' => '2026']))
            ->assertRedirect(route('login'));
    }

    private function member(string $nis, string $name, string $year = '2026'): Member
    {
        return Member::create([
            'nis' => $nis,
            'full_name' => $name,
            'graduation_year' => $year,
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
