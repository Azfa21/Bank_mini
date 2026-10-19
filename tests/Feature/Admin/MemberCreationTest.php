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

    public function test_create_and_edit_pages_render_without_status_field(): void
    {
        $member = Member::create([
            'nis' => '20265000',
            'full_name' => 'Fajar Nugroho',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.members.create'))
            ->assertOk()
            ->assertSee('Tambah Anggota')
            ->assertDontSee('name="status"');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.members.edit', $member))
            ->assertOk()
            ->assertSee('Fajar Nugroho')
            ->assertDontSee('name="status"');
    }

    public function test_status_is_managed_by_activation_not_by_the_form(): void
    {
        // Form Anggota tidak lagi punya pilihan status. Nilai status yang masih dikirim
        // klien lama harus diabaikan, bukan membuat anggota tanpa rekening aktif.
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.store'), [
                'full_name' => 'Budi Santoso',
                'status' => Member::STATUS_ACCOUNT_ACTIVE,
            ])
            ->assertRedirect(route('admin.members.index'));

        $member = Member::where('full_name', 'Budi Santoso')->firstOrFail();

        $this->assertSame(Member::STATUS_ACCOUNT_INACTIVE, $member->status);
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

    public function test_member_can_be_created_and_updated_with_class(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.store'), [
                'full_name' => 'Gita Pertiwi',
                'class_name' => 'X RPL 1',
            ])
            ->assertRedirect(route('admin.members.index'));

        $member = Member::where('full_name', 'Gita Pertiwi')->firstOrFail();

        $this->assertSame('X RPL 1', $member->class_name);

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.members.update', $member), [
                'full_name' => 'Gita Pertiwi',
                'class_name' => 'XI RPL 1',
            ])
            ->assertRedirect(route('admin.members.index'));

        $this->assertSame('XI RPL 1', $member->refresh()->class_name);
    }

    public function test_member_list_shows_class_column_and_can_be_searched_by_class(): void
    {
        Member::create([
            'nis' => '20266001',
            'full_name' => 'Hana Kusuma',
            'class_name' => 'XI TKJ 2',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);

        Member::create([
            'nis' => '20266002',
            'full_name' => 'Indra Prakoso',
            'class_name' => 'X RPL 1',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.members.index', ['search' => 'TKJ']))
            ->assertOk()
            ->assertSee('XI TKJ 2')
            ->assertSee('Hana Kusuma')
            ->assertDontSee('Indra Prakoso');
    }

    public function test_member_form_offers_class_suggestions(): void
    {
        Member::create([
            'nis' => '20266001',
            'full_name' => 'Hana Kusuma',
            'class_name' => 'XI RPL 7',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.members.create'))
            ->assertOk()
            ->assertSee('name="class_name"', false)
            ->assertSee('list="kelas-opsi"', false)
            ->assertSee('<option value="XI RPL 7"></option>', false);
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
