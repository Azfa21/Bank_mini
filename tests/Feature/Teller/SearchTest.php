<?php

namespace Tests\Feature\Teller;

class SearchTest extends TellerTestCase
{
    public function test_teller_can_open_search_page(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.members.search'))
            ->assertOk()
            ->assertSee('Pencarian Anggota');
    }

    public function test_search_by_account_number_redirects_to_profile(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.members.lookup', ['q' => '2026/2027-001']))
            ->assertRedirect(route('teller.members.show', $this->account));
    }

    public function test_search_by_qr_code_value_redirects_to_profile(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.members.lookup', ['q' => 'qr-test-1']))
            ->assertRedirect(route('teller.members.show', $this->account));
    }

    public function test_search_with_unknown_value_shows_error(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.members.search'))
            ->get(route('teller.members.lookup', ['q' => 'TIDAK-ADA']))
            ->assertRedirect(route('teller.members.search'))
            ->assertSessionHas('error');
    }

    public function test_search_with_empty_query_shows_error(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.members.search'))
            ->get(route('teller.members.lookup', ['q' => '   ']))
            ->assertRedirect(route('teller.members.search'))
            ->assertSessionHas('error');
    }

    public function test_profile_shows_balance_and_action_buttons(): void
    {
        $this->actingAsTeller()
            ->get(route('teller.members.show', $this->account))
            ->assertOk()
            ->assertSee('Ani Wijaya')
            ->assertSee('25.000')
            ->assertSee(route('teller.deposits.create', $this->account))
            ->assertSee(route('teller.withdrawals.create', $this->account));
    }

    public function test_admin_cannot_access_teller_search(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('teller.members.search'))
            ->assertRedirect(route('login'));
    }

    public function test_supervisor_cannot_access_teller_lookup(): void
    {
        $this->actingAs($this->supervisor, 'web')
            ->get(route('teller.members.lookup', ['q' => '2026/2027-001']))
            ->assertRedirect(route('login'));
    }

    public function test_nasabah_cannot_access_teller_profile(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('teller.members.show', $this->account))
            ->assertRedirect(route('login'));
    }
}
