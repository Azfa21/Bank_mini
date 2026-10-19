<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Saver;
use App\Services\AccountService;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Teller\TellerTestCase;

/**
 * Kenaikan kelas akhir tahun ajaran: satu kali proses memindahkan seluruh kelas ke
 * tingkat berikutnya, plus alat pindah rombel untuk membagi atau menggabungkan kelas.
 */
class ClassPromotionTest extends TellerTestCase
{
    public function test_page_lists_class_groups_and_promotable_total(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $this->saver('2026002', 'Sinta Dewi', 'X RPL 1');
        $this->saver('2026003', 'Tomi Sanjaya', 'XI TKJ 2');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('Kenaikan kelas')
            ->assertSee('X RPL 1')
            ->assertSee('XI TKJ 2')
            ->assertSee('Naikkan 3 penabung');
    }

    public function test_page_points_final_year_students_to_graduation(): void
    {
        $this->saver('2026001', 'Umar Alumni', 'XII RPL 1');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('penabung kelas XII tidak ikut dinaikkan')
            ->assertSee('Buka Kelulusan')
            ->assertSee('Tidak dinaikkan. Arsipkan angkatan ini di halaman Kelulusan.');
    }

    public function test_page_lists_savers_without_class(): void
    {
        $this->saver('2026001', 'Vina Tanpa Kelas', '');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('Penabung yang kelasnya belum diisi')
            ->assertSee('Vina Tanpa Kelas');
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
                    ['class' => 'X RPL 1', 'level' => 'XI', 'major' => 'RPL', 'rombel' => '1'],
                ],
            ])
            ->assertRedirect(route('admin.class-promotions.index'))
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 2 penabung di 1 kelas.');

        $this->assertSame('XI RPL 1', $active->refresh()->class_name);
        $this->assertSame('XI RPL 1', $blocked->refresh()->class_name);
        $this->assertSame('X RPL 2', $other->refresh()->class_name);
        $this->assertSame('X RPL 1', $alumni->refresh()->class_name);
    }

    public function test_promotion_can_merge_two_classes_into_one_target(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $first = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $second = $this->saver('2026002', 'Sinta Dewi', 'X RPL 2');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'level' => 'XI', 'major' => 'RPL', 'rombel' => '1'],
                    ['class' => 'X RPL 2', 'level' => 'XI', 'major' => 'RPL', 'rombel' => '1'],
                ],
            ])
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 2 penabung di 1 kelas.');

        $this->assertSame('XI RPL 1', $first->refresh()->class_name);
        $this->assertSame('XI RPL 1', $second->refresh()->class_name);
    }

    public function test_promotion_does_not_move_a_saver_twice_in_one_run(): void
    {
        // Kelas asal satu baris bisa menjadi kelas tujuan baris lain (X RPL 1 -> XI RPL 1,
        // sementara XI RPL 1 -> XII RPL 1). Pembacaan penabung harus memakai keadaan awal.
        $this->moveDefaultSaverToFinalYear();

        $fresh = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $current = $this->saver('2026002', 'Sinta Dewi', 'XI RPL 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'level' => 'XI', 'major' => 'RPL', 'rombel' => '1'],
                    ['class' => 'XI RPL 1', 'level' => 'XII', 'major' => 'RPL', 'rombel' => '1'],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertSame('XI RPL 1', $fresh->refresh()->class_name);
        $this->assertSame('XII RPL 1', $current->refresh()->class_name);
    }

    public function test_promotion_skips_rows_without_a_complete_target(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $saver = $this->saver('2026001', 'Rizky Ramadhan', 'VII A');
        $untouched = $this->saver('2026002', 'Sinta Dewi', 'X RPL 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'VII A', 'level' => '', 'major' => 'RPL', 'rombel' => '1'],
                    ['class' => 'X RPL 1', 'level' => 'X', 'major' => 'RPL', 'rombel' => '1'],
                    ['class' => 'X RPL 2', 'level' => 'XI', 'major' => 'RPL', 'rombel' => '1'],
                ],
            ])
            ->assertSessionHas('success', 'Belum ada penabung yang dipindahkan. Isi dulu kelas tujuan pada baris yang ingin diproses. 3 baris kelas dilewati karena kelas tujuannya belum lengkap atau sama dengan kelas asal.');

        $this->assertSame('VII A', $saver->refresh()->class_name);
        $this->assertSame('X RPL 1', $untouched->refresh()->class_name);
    }

    public function test_promotion_maps_a_legacy_class_manually(): void
    {
        $saver = $this->saver('2026001', 'Rizky Ramadhan', 'VII A');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'VII A', 'level' => 'X', 'major' => 'RPL', 'rombel' => '1'],
                ],
            ])
            ->assertSessionHas('success', 'Kenaikan kelas selesai: 1 penabung di 1 kelas.');

        $this->assertSame('X RPL 1', $saver->refresh()->class_name);
    }

    public function test_promotion_needs_an_extra_confirmation_when_run_twice_this_year(): void
    {
        $saver = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');

        $rows = [
            ['class' => 'X RPL 1', 'level' => 'XI', 'major' => 'RPL', 'rombel' => '1'],
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

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.store'), [
                'academic_year' => AccountService::academicYear(),
                'rows' => [
                    ['class' => 'X RPL 1', 'level' => 'XI', 'major' => 'RPL', 'rombel' => '1'],
                ],
            ]);

        $log = AuditLog::where('action', 'class_promotion_processed')->firstOrFail();

        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame(['X RPL 1' => 'XI RPL 1'], $log->metadata['mapping']);
        $this->assertSame(1, $log->metadata['savers']);
    }

    public function test_move_sends_selected_savers_to_another_rombel(): void
    {
        $moved = $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $movedBlocked = $this->saver('2026002', 'Sinta Dewi', 'X RPL 1', 'blocked');
        $stays = $this->saver('2026003', 'Tomi Sanjaya', 'X RPL 1');
        $alumni = $this->saver('2026004', 'Umar Alumni', 'X RPL 1', 'graduated');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.move'), [
                'saver_ids' => [$moved->id, $movedBlocked->id, $alumni->id],
                'level' => 'X',
                'major' => 'RPL',
                'rombel' => '3',
            ])
            ->assertRedirect(route('admin.class-promotions.index', ['class' => 'X RPL 3']))
            ->assertSessionHas('success', '2 penabung dipindahkan ke X RPL 3.');

        $this->assertSame('X RPL 3', $moved->refresh()->class_name);
        $this->assertSame('X RPL 3', $movedBlocked->refresh()->class_name);
        $this->assertSame('X RPL 1', $stays->refresh()->class_name);
        $this->assertSame('X RPL 1', $alumni->refresh()->class_name);

        $this->assertDatabaseHas('audit_logs', ['action' => 'class_members_moved']);
    }

    public function test_move_requires_at_least_one_saver(): void
    {
        $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.class-promotions.move'), [
                'level' => 'X',
                'major' => 'RPL',
                'rombel' => '2',
            ])
            ->assertSessionHasErrors('saver_ids');
    }

    public function test_adjustment_list_filters_savers_by_class(): void
    {
        $this->moveDefaultSaverToFinalYear();

        $this->saver('2026001', 'Rizky Ramadhan', 'X RPL 1');
        $this->saver('2026002', 'Sinta Dewi', 'X TKJ 1');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index', ['class' => 'X RPL 1']))
            ->assertOk()
            ->assertSee('Rizky Ramadhan')
            ->assertDontSee('Sinta Dewi');
    }

    public function test_teller_cannot_open_or_run_the_promotion_page(): void
    {
        $this->actingAsTeller()
            ->get(route('admin.class-promotions.index'))
            ->assertRedirect(route('login'));

        $this->actingAsTeller()
            ->post(route('admin.class-promotions.move'), [
                'saver_ids' => [$this->nasabah->id],
                'level' => 'X',
                'major' => 'RPL',
                'rombel' => '2',
            ])
            ->assertRedirect(route('login'));
    }

    /**
     * Data uji bawaan membuat satu penabung di X RPL 1. Kelasnya dipindahkan dulu supaya
     * tidak ikut terhitung di perhitungan kelas yang diuji di sini.
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
}
