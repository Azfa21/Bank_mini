<?php

namespace Tests\Feature\Admin;

use App\Models\Member;
use Tests\Feature\Teller\TellerTestCase;

class MemberListFilterTest extends TellerTestCase
{
    public function test_members_page_hides_members_who_already_became_savers(): void
    {
        $this->createMember('2023001', 'Ani Wijaya', Member::STATUS_ACCOUNT_ACTIVE);
        $this->createMember('2023002', 'Budi Santoso');
        $this->nasabah->update(['member_nis' => '2023001']);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Ani Wijaya');
    }

    public function test_member_of_soft_deleted_saver_stays_hidden(): void
    {
        $this->createMember('2023001', 'Ani Wijaya', Member::STATUS_ACCOUNT_ACTIVE);
        $this->nasabah->update(['member_nis' => '2023001']);
        $this->nasabah->delete();

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertDontSee('Ani Wijaya')
            ->assertSee('Semua anggota sudah punya rekening penabung');
    }

    public function test_members_page_has_no_account_status_column_or_filter(): void
    {
        $this->createMember('2023002', 'Budi Santoso');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Status Rekening')
            ->assertDontSee('Rekening tidak aktif')
            ->assertDontSee('name="status"');
    }

    public function test_empty_state_when_no_member_exists(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('Belum ada data anggota');
    }

    private function createMember(string $nis, string $fullName, string $status = Member::STATUS_ACCOUNT_INACTIVE): Member
    {
        return Member::create([
            'nis' => $nis,
            'full_name' => $fullName,
            'status' => $status,
            'created_by' => $this->admin->id,
        ]);
    }
}
