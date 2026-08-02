<?php

namespace Tests\Feature;

use Tests\Feature\Teller\TellerTestCase;

class NavigationTest extends TellerTestCase
{
    public function test_admin_dashboard_shows_role_menu(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Kelola Pengguna')
            ->assertSee('Kelola Nasabah')
            ->assertSee('Konfigurasi')
            ->assertSee('Audit Jurnal');
    }

    public function test_supervisor_dashboard_shows_role_menu(): void
    {
        $this->actingAs($this->supervisor, 'web')
            ->get(route('supervisor.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Laporan Harian')
            ->assertSee('Audit Jurnal');
    }

    public function test_nasabah_dashboard_shows_role_menu(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('nasabah.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Riwayat Mutasi');
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
            ->get(route('teller.customers.search'))
            ->assertOk()
            ->assertSee(route('teller.dashboard'))
            ->assertSee('Pencarian Nasabah');
    }

    public function test_nasabah_dashboard_shows_navbar(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('nasabah.dashboard'))
            ->assertOk()
            ->assertSee(route('nasabah.dashboard'));
    }
}
