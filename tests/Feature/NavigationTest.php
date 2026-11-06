<?php

namespace Tests\Feature;

use Tests\Feature\Teller\TellerTestCase;

class NavigationTest extends TellerTestCase
{
    public function test_root_url_redirects_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_admin_dashboard_shows_role_menu(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Kelola Pengguna')
            ->assertSee('Kelola Anggota')
            ->assertSee('Kelola Penabung')
            ->assertSee('Kelulusan')
            ->assertSee('Kenaikan Kelas')
            ->assertSee('Laporan')
            ->assertSee('Konfigurasi')
            ->assertSee('Audit Jurnal');
    }

    public function test_promotion_page_breadcrumb_uses_the_data_group(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.class-promotions.index'))
            ->assertOk()
            ->assertSee('aria-label="Posisi halaman"', false)
            ->assertSee('>Data</li>', false)
            ->assertSee('>Kenaikan Kelas</li>', false);
    }

    public function test_admin_submenu_shows_dashboard_link_in_navbar(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.dashboard'))
            ->assertSee('Kelola Pengguna');
    }

    public function test_teller_search_page_shows_navbar_with_dashboard_link(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.members.search'))
            ->assertOk()
            ->assertSee(route('teller.dashboard'))
            ->assertSee('Pencarian Penabung');
    }

    public function test_admin_page_shows_breadcrumb_with_the_active_menu_label(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.savers.index'))
            ->assertOk()
            ->assertSee('aria-label="Posisi halaman"', false)
            ->assertSee('aria-current="page" class="font-medium text-stone-900" >Kelola Penabung</li>', false);
    }

    public function test_teller_breadcrumb_starts_from_the_teller_dashboard(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.members.search'))
            ->assertOk()
            ->assertSee('aria-label="Posisi halaman"', false)
            ->assertSee('>Dashboard</a>', false)
            ->assertSee(route('teller.dashboard'));
    }
}
