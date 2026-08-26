<?php

namespace Tests\Feature;

use Tests\Feature\Teller\TellerTestCase;

class CardPrintTest extends TellerTestCase
{
    public function test_admin_can_view_card_print_page(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.accounts.card-print', $this->account))
            ->assertOk()
            ->assertSee('BANK MINI SEKOLAH')
            ->assertSee($this->account->account_number)
            ->assertSee($this->nasabah->full_name);
    }

    public function test_teller_and_supervisor_cannot_access_admin_card_print(): void
    {
        foreach ([$this->teller, $this->supervisor] as $user) {
            $this->actingAs($user, 'web')
                ->get(route('admin.accounts.card-print', $this->account))
                ->assertStatus(302);
        }
    }

    public function test_guest_cannot_access_admin_card_print(): void
    {
        $this->get(route('admin.accounts.card-print', $this->account))
            ->assertRedirect(route('login'));
    }

    public function test_nasabah_can_view_own_card_print_page(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('nasabah.card.print'))
            ->assertOk()
            ->assertSee('BANK MINI SEKOLAH')
            ->assertSee($this->nasabah->full_name);
    }

    public function test_card_print_page_contains_qr_image(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.accounts.card-print', $this->account));

        $qrUrl = route('qr.show', ['data' => $this->nasabah->qr_code_value, 'size' => 300]);

        $response->assertOk()->assertSee('qr-test-1')->assertSee('size=300');
    }
}
