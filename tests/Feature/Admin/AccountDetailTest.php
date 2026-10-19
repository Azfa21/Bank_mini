<?php

namespace Tests\Feature\Admin;

use Tests\Feature\Teller\TellerTestCase;

class AccountDetailTest extends TellerTestCase
{
    public function test_admin_can_open_account_detail_page(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.accounts.show', $this->account))
            ->assertOk()
            ->assertSee('Detail Rekening')
            ->assertSee($this->account->account_number)
            ->assertSee($this->nasabah->full_name);
    }

    public function test_account_detail_shows_dash_for_missing_dates(): void
    {
        $this->account->update(['closed_at' => null]);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.accounts.show', $this->account))
            ->assertOk()
            ->assertSee('Ditutup: -', false);
    }

    public function test_back_link_keeps_the_filtered_saver_list(): void
    {
        $listUrl = route('admin.savers.index', ['search' => 'Ani', 'status' => 'active']);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.accounts.show', ['account' => $this->account, 'from' => $listUrl]))
            ->assertOk()
            ->assertSee($listUrl)
            ->assertDontSee('Pengguna tidak ditemukan');
    }

    public function test_back_link_ignores_address_from_another_site(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.accounts.show', ['account' => $this->account, 'from' => 'https://contoh.test/admin/savers']))
            ->assertOk()
            ->assertSee(route('admin.savers.index'))
            ->assertDontSee('contoh.test');
    }
}
