<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Saver;
use App\Services\AccountService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Teller\TellerTestCase;

/**
 * Kenaikan kelas akhir tahun ajaran: satu proses memindahkan seluruh kelas ke tingkat
 * berikutnya, plus tindakan tinggal kelas. Jurusan dan rombel selalu mengikuti kelas asal.
 */
class ClassPromotionTest extends TellerTestCase
{
    public function test_page_lists_class_groups_with_member_and_saver_counts(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $this->saver('2026002', 'Sinta Dewi', 'X RPL 1');
        $this->saver('2026003', 'Tomi Sanjaya', 'XI TKJ 2');
        $this->member('2026101', 'Bagas Tanpa Rekening', 'X RPL 1');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('Kenaikan kelas')
            ->assertSee('X RPL 1')
            ->assertSee('XI TKJ 2')
            ->assertSee('Anggota')
            ->assertSee('Penabung')
            ->assertSee('Naik ke XI RPL 1')
            ->assertSee('Proses kenaikan kelas');
    }

    public function test_page_has_no_class_transfer_controls(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertDontSee('Sesuaikan rombel')
            ->assertDontSee('Jurusan tujuan')
            ->assertDontSee('name="major"')
            ->assertDontSee('name="level"');

        $this->assertFalse(Route::has('admin.class-promotions.move'));
    }

    public function test_page_points_final_year_students_to_graduation(): void
    {
        $this->saver('2026001', 'Umar Alumni', 'XII RPL 1');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('kelas XII tidak ikut dinaikkan')
            ->assertSee('Buka Kelulusan')
            ->assertSee('Tidak dinaikkan. Arsipkan angkatan ini di halaman Kelulusan.');
    }

    public function test_page_lists_members_and_savers_without_class(): void
    {
        $this->saver('2026001', 'Vina Tanpa Kelas', '');
        $this->member('2026101', 'Yoga Tanpa Kelas', null);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('Anggota dan penabung yang kelasnya belum diisi')
            ->assertSee('Vina Tanpa Kelas')
            ->assertSee('Yoga Tanpa Kelas');
    }

    public function test_page_marks_classes_with_an_unknown_level_instead_of_offering_a_target(): void
    {
        $this->moveDefaultSaverToFinalYear();
        $this->saver('2026001', 'Rizky Ramadhan', 'VII A');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('Tingkat tidak dikenali')
            ->assertDontSee('rows[');
    }

    public function test_promotion_moves_active_and_blocked_savers_to_the_next_level(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $active = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $blocked = $this->saver('2026002', 'Sinta Dewi', 'X RPL 1', 'blocked');
        $other = $this->saver('2026003', 'Tomi Sanjaya', 'X RPL 2');
        $alumni = $this->saver('2026004', 'Umar Alumni', 'X RPL 1', 'graduated');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote'],
                ],
            ])
            ->assertRedirect(route('admin.class-promotions.index'))
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 2 penabung di 1 kelas.');

        $this->assertSame('XI RPL 1', $active->refresh()->class_name);
        $this->assertSame('XI RPL 1', $blocked->refresh()->class_name);
        $this->assertSame('X RPL 2', $other->refresh()->class_name);
        $this->assertSame('X RPL 1', $alumni->refresh()->class_name);
    }

    public function test_promotion_moves_members_who_do_not_have_an_account_yet(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $member = $this->member('2026101', 'Bagas Tanpa Rekening', 'X RPL 1');
        $graduated = $this->member('2026102', 'Umar Alumni', 'X RPL 1', true);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote'],
                ],
            ])
            ->assertRedirect(route('admin.class-promotions.index'))
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 1 anggota di 1 kelas.');

        $this->assertSame('XI RPL 1', $member->refresh()->class_name);
        $this->assertSame('X RPL 1', $graduated->refresh()->class_name);
    }

    public function test_promotion_updates_member_and_saver_of_the_same_student(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $member = $this->member('2026101', 'Bagas Punya Rekening', 'X RPL 1');
        $saver = $this->saver('2026101', 'Bagas Punya Rekening', 'X RPL 1');
        $saver->update(['member_nis' => $member->nis]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote'],
                ],
            ])
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 1 penabung di 1 kelas.');

        $this->assertSame('XI RPL 1', $saver->refresh()->class_name);
        $this->assertSame('XI RPL 1', $member->refresh()->class_name);
    }

    public function test_classes_marked_to_stay_keep_their_current_class(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $promotes = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $retains = $this->saver('2026002', 'Sinta Dewi', 'X TKJ 1');
        $retainedMember = $this->member('2026101', 'Yoga Tinggal Kelas', 'X TKJ 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote'],
                    ['class' => 'X TKJ 1', 'action' => 'retain'],
                ],
            ])
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 1 penabung di 1 kelas. 1 kelas ditandai tinggal kelas.');

        $this->assertSame('XI RPL 1', $promotes->refresh()->class_name);
        $this->assertSame('X TKJ 1', $retains->refresh()->class_name);
        $this->assertSame('X TKJ 1', $retainedMember->refresh()->class_name);
    }

    public function test_promotion_does_not_move_a_saver_twice_in_one_run(): void
    {
        // Kelas asal satu baris bisa menjadi kelas tujuan baris lain (X RPL 1 -> XI RPL 1,
        // sementara XI RPL 1 -> XII RPL 1). Pembacaan sasaran harus memakai keadaan awal.
        $this->moveDefaultSaverToFinalYear();

        $fresh = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $current = $this->saver('2026002', 'Sinta Dewi', 'XI RPL 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote'],
                    ['class' => 'XI RPL 1', 'action' => 'promote'],
                ],
            ])
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 2 penabung di 2 kelas.');

        $this->assertSame('XI RPL 1', $fresh->refresh()->class_name);
        $this->assertSame('XII RPL 1', $current->refresh()->class_name);
    }

    public function test_promotion_keeps_a_class_whose_level_is_not_recognized(): void
    {
        $saver = $this->saver('2026001', 'Rizky Ramadhan', 'VII A');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'VII A', 'action' => 'promote'],
                ],
            ])
            ->assertSessionHas('success', 'Belum ada yang dinaikkan. Pilih Naik ke tingkat berikutnya pada minimal satu kelas. 1 baris kelas dilewati karena tingkatnya tidak dikenali atau datanya sudah tidak ada.');

        $this->assertSame('VII A', $saver->refresh()->class_name);
    }

    public function test_promotion_keeps_the_major_and_rombel_of_the_source_class(): void
    {
        $saver = $this->saver('2026001', 'Rizky Ramadhan', 'X TKJ 3');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X TKJ 3', 'action' => 'promote'],
                    // Jurusan lain pada baris manapun tidak bisa mengubah hasilnya.
                    ['class' => 'X TKJ 3', 'action' => 'promote', 'major' => 'RPL'],
                ],
            ])
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 1 penabung di 1 kelas.');

        $this->assertSame('XI TKJ 3', $saver->refresh()->class_name);
    }

    public function test_promotion_needs_an_extra_confirmation_when_run_twice_this_year(): void
    {
        $saver = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');

        $rows = [
            ['class' => 'X RPL 1', 'action' => 'promote'],
        ];

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => $rows,
            ])
            ->assertSessionHas('success');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('sudah pernah dijalankan');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => $rows,
            ])
            ->assertSessionHas('error', 'Kenaikan kelas untuk tahun ajaran '.AccountService::academicYear().' sudah pernah dijalankan. Centang konfirmasi kalau memang ingin menjalankannya lagi.');

        $this->assertSame('XI RPL 1', $saver->refresh()->class_name);
    }

    public function test_promotion_records_the_run_in_the_audit_log(): void
    {
        $this->moveDefaultSaverToFinalYear();
        $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $this->member('2026101', 'Bagas Tanpa Rekening', 'X RPL 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote'],
                ],
            ]);

        $log = AuditLog::where('action', 'class_promotion_processed')->firstOrFail();

        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame(['X RPL 1' => 'XI RPL 1'], $log->metadata['mapping']);
        $this->assertSame(1, $log->metadata['members']);
        $this->assertSame(1, $log->metadata['savers']);
    }

    public function test_page_lists_the_students_of_each_class_for_retention(): void
    {
        $this->moveDefaultSaverToFinalYear();
        $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $this->saver('2026002', 'Sinta Dewi', 'X RPL 1', 'blocked');
        $this->member('2026101', 'Bagas Tanpa Rekening', 'X RPL 1');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('Pilih siswa tinggal kelas')
            ->assertSee('rows[0][retain][]', false)
            ->assertSee('saver:2026002', false)
            ->assertSee('member:2026101', false)
            ->assertSee('Rizky Ramadhan')
            ->assertSee('Diblokir')
            ->assertSee('3 siswa');
    }

    public function test_a_student_with_an_account_appears_once_in_the_retention_list(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $member = $this->member('2026101', 'Bagas Punya Rekening', 'X RPL 1');
        $saver = $this->saver('2026101', 'Bagas Punya Rekening', 'X RPL 1');
        $saver->update(['member_nis' => $member->nis]);

        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk();

        $page = $response->getContent();

        $this->assertSame(1, substr_count($page, 'value="saver:2026101"'));
        $this->assertSame(0, substr_count($page, 'member:2026101'));
    }

    public function test_checked_students_stay_in_their_class_while_classmates_move(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $movingSaver = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $stayingSaver = $this->saver('2026002', 'Sinta Dewi', 'X RPL 1', 'blocked');
        $stayingMember = $this->member('2026101', 'Bagas Tinggal Kelas', 'X RPL 1');
        $movingMember = $this->member('2026102', 'Doni Naik Kelas', 'X RPL 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote', 'retain' => ['saver:2026002', 'member:2026101']],
                ],
            ])
            ->assertRedirect(route('admin.class-promotions.index'))
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 1 anggota dan 1 penabung di 1 kelas. 2 siswa ditandai tinggal kelas.');

        $this->assertSame('XI RPL 1', $movingSaver->refresh()->class_name);
        $this->assertSame('X RPL 1', $stayingSaver->refresh()->class_name);
        $this->assertSame('X RPL 1', $stayingMember->refresh()->class_name);
        $this->assertSame('XI RPL 1', $movingMember->refresh()->class_name);
        // Penabung yang diblokir tetap diblokir saat ditandai tinggal kelas.
        $this->assertSame(Saver::STATUS_BLOCKED, $stayingSaver->refresh()->status);
    }

    public function test_retention_moves_the_member_linked_to_a_retained_saver_nowhere(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $member = $this->member('2026101', 'Bagas Punya Rekening', 'X RPL 1');
        $saver = $this->saver('2026101', 'Bagas Punya Rekening', 'X RPL 1');
        $saver->update(['member_nis' => $member->nis]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote', 'retain' => ['saver:2026101']],
                ],
            ])
            ->assertSessionHas('success', 'Belum ada yang dinaikkan. 1 siswa ditandai tinggal kelas.');

        $this->assertSame('X RPL 1', $saver->refresh()->class_name);
        $this->assertSame('X RPL 1', $member->refresh()->class_name);
    }

    public function test_a_class_where_everyone_stays_is_not_reported_as_skipped(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $saver = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote', 'retain' => ['saver:2026001']],
                ],
            ])
            ->assertSessionHas('success', 'Belum ada yang dinaikkan. 1 siswa ditandai tinggal kelas.');

        $this->assertSame('X RPL 1', $saver->refresh()->class_name);
    }

    public function test_retention_keys_that_do_not_belong_to_the_class_are_ignored(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $saver = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $other = $this->saver('2026003', 'Tomi Sanjaya', 'X TKJ 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote', 'retain' => ['saver:2026003', 'member:000000']],
                ],
            ])
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 1 penabung di 1 kelas.');

        $this->assertSame('XI RPL 1', $saver->refresh()->class_name);
        $this->assertSame('X TKJ 1', $other->refresh()->class_name);
    }

    public function test_retention_decisions_are_recorded_in_the_audit_log(): void
    {
        $this->moveDefaultSaverToFinalYear();
        $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $this->saver('2026002', 'Sinta Dewi', 'X RPL 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote', 'retain' => ['saver:2026002']],
                ],
            ]);

        $log = AuditLog::where('action', 'class_promotion_processed')->firstOrFail();

        $this->assertSame(1, $log->metadata['retained_students']);
        $this->assertSame(['X RPL 1' => ['saver:2026002']], $log->metadata['retained_student_keys']);
    }

    public function test_retention_rejects_a_payload_that_is_not_a_student_key(): void
    {
        $saver = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote', 'retain' => [str_repeat('x', 41)]],
                ],
            ])
            ->assertSessionHasErrors('rows.0.retain.0');

        $this->assertSame('X RPL 1', $saver->refresh()->class_name);
    }

    public function test_teller_cannot_open_or_run_the_promotion_page(): void
    {
        $this->actingAsTeller()
            ->get(route('admin.class-promotions.index'))
            ->assertRedirect(route('login'));

        $this->actingAsTeller()
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'action' => 'promote'],
                ],
            ])
            ->assertRedirect(route('login'));
    }

    /**
     * Data uji bawaan membuat satu penabung di X RPL 1. Kelasnya dipindahkan dulu supaya
     * tidak ikut terhitung pada perhitungan kelas yang diuji di sini.
     */
    private function moveDefaultSaverToFinalYear(): void
    {
        $this->nasabah->update(['class_name' => 'XII RPL 9']);
    }

    private function saver(string $nis, string $name, string $class, string $status = 'active'): Saver
    {
        return Saver::create([
            'student_number' => $nis,
            'full_name' => $name,
            'class_name' => $class,
            'email' => $nis.'@example.test',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-'.$nis,
            'created_by' => $this->admin->id,
            'status' => $status,
        ]);
    }

    private function member(string $nis, string $name, ?string $class, bool $graduated = false): Member
    {
        return Member::create([
            'nis' => $nis,
            'full_name' => $name,
            'class_name' => $class,
            'graduation_year' => $graduated ? now()->format('Y') : null,
            'graduated_at' => $graduated ? now() : null,
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);
    }
}
