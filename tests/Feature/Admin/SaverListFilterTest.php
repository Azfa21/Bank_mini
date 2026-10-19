<?php

namespace Tests\Feature\Admin;

use App\Models\Saver;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Teller\TellerTestCase;

/**
 * Daftar Penabung dipakai harian oleh administrator. Filter harus terbaca, hasil
 * pencarian harus ikut saat pindah halaman, dan daftar kosong harus menjelaskan
 * sebabnya (belum ada data atau tidak cocok dengan filter).
 */
class SaverListFilterTest extends TellerTestCase
{
    public function test_pagination_links_keep_the_active_filter(): void
    {
        $this->createSavers(16);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.savers.index', ['search' => 'Uji', 'page' => 2]))
            ->assertOk()
            ->assertSee('Penabung Uji')
            ->assertSee('search=Uji');
    }

    public function test_reset_link_only_appears_when_a_filter_is_active(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.savers.index'))
            ->assertOk()
            ->assertDontSee('>Reset</a>', false);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.savers.index', ['status' => 'blocked']))
            ->assertOk()
            ->assertSee('>Reset</a>', false);
    }

    public function test_empty_state_tells_no_match_apart_from_no_data(): void
    {
        $this->nasabah->delete();

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.savers.index'))
            ->assertOk()
            ->assertSee('Belum ada data penabung.');

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.savers.index', ['status' => 'graduated']))
            ->assertOk()
            ->assertSee('Tidak ada penabung yang cocok dengan filter ini.');
    }

    protected function createSavers(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Saver::create([
                'student_number' => sprintf('910%02d', $i),
                'full_name' => sprintf('Penabung Uji %02d', $i),
                'class_name' => 'XI RPL 1',
                'pin_hash' => Hash::make('123456'),
                'qr_code_value' => 'qr-saver-list-'.$i,
                'created_by' => $this->admin->id,
                'status' => 'active',
            ]);
        }
    }
}
