<?php

namespace Tests\Feature\Admin;

use App\Models\Member;
use Tests\Feature\Teller\TellerTestCase;

class MemberCreationTest extends TellerTestCase
{
    public function test_store_generates_nis_when_blank(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.store'), [
                'full_name' => 'Budi Santoso',
                'graduation_year' => '2026',
            ])
            ->assertRedirect(route('admin.members.index'));

        $member = Member::where('full_name', 'Budi Santoso')->firstOrFail();

        $this->assertSame(now()->format('Y').'0001', $member->nis);
        $this->assertSame(Member::STATUS_ACCOUNT_INACTIVE, $member->status);
        $this->assertDatabaseCount('savers', 1);
        $this->assertDatabaseCount('accounts', 1);
    }

    public function test_consecutive_creations_produce_sequential_nis(): void
    {
        foreach (['Citra Lestari', 'Dewi Anggraini'] as $name) {
            $this->actingAs($this->admin, 'web')
                ->post(route('admin.members.store'), ['full_name' => $name])
                ->assertRedirect(route('admin.members.index'));
        }

        $members = Member::whereIn('full_name', ['Citra Lestari', 'Dewi Anggraini'])
            ->orderBy('created_at')
            ->orderBy('nis')
            ->get();

        $this->assertCount(2, $members);
        $this->assertSame(
            [now()->format('Y').'0001', now()->format('Y').'0002'],
            $members->pluck('nis')->all(),
        );
    }

    public function test_store_requires_unique_nis(): void
    {
        Member::create([
            'nis' => '2023001',
            'full_name' => 'Sudah Ada',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.store'), [
                'nis' => '2023001',
                'full_name' => 'Duplikat NIS',
            ])
            ->assertSessionHasErrors('nis');

        $this->assertDatabaseMissing('members', ['full_name' => 'Duplikat NIS']);
    }

    public function test_member_can_be_updated(): void
    {
        $member = Member::create([
            'nis' => '20265000',
            'full_name' => 'Fajar Nugroho',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.members.update', $member), [
                'full_name' => 'Fajar Nugroho Putra',
                'graduation_year' => '2027',
                'status' => Member::STATUS_ACCOUNT_INACTIVE,
            ])
            ->assertRedirect(route('admin.members.index'));

        $this->assertDatabaseHas('members', [
            'nis' => '20265000',
            'full_name' => 'Fajar Nugroho Putra',
            'graduation_year' => '2027',
        ]);
    }

    public function test_member_without_saver_can_be_deleted(): void
    {
        $member = Member::create([
            'nis' => '20265001',
            'full_name' => 'Hapus Anggota',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.members.destroy', $member))
            ->assertRedirect(route('admin.members.index'));

        $this->assertDatabaseMissing('members', ['nis' => '20265001']);
    }

    public function test_member_with_saver_cannot_be_deleted(): void
    {
        $member = Member::create([
            'nis' => '2023001',
            'full_name' => 'Ani Wijaya',
            'status' => Member::STATUS_ACCOUNT_ACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->nasabah->update(['member_nis' => $member->nis]);

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.members.destroy', $member))
            ->assertRedirect(route('admin.members.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('members', ['nis' => '2023001']);
    }

    public function test_member_with_soft_deleted_saver_cannot_be_deleted(): void
    {
        $member = Member::create([
            'nis' => '2023001',
            'full_name' => 'Ani Wijaya',
            'status' => Member::STATUS_ACCOUNT_ACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->nasabah->update(['member_nis' => $member->nis]);
        $this->nasabah->delete();

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.members.destroy', $member))
            ->assertRedirect(route('admin.members.index'))
            ->assertSessionHas('error', 'Anggota tidak dapat dihapus karena memiliki histori rekening penabung.');

        $this->assertDatabaseHas('members', ['nis' => '2023001']);
    }
}
