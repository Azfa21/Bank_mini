<?php

namespace Tests\Feature\Admin;

use App\Support\ClassRoom;
use Tests\Feature\Teller\TellerTestCase;

class SystemConfigTest extends TellerTestCase
{
    public function test_admin_updates_all_configuration_keys(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put(route('admin.config.update'), [
                'bank_name' => 'Bank Mini SMK Nusantara',
                'minimum_balance' => 15000,
                'min_deposit_to_activate' => 20000,
            ])
            ->assertRedirect(route('admin.config.edit'));

        $this->assertDatabaseHas('system_configs', ['config_key' => 'bank_name', 'config_value' => 'Bank Mini SMK Nusantara']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'minimum_balance', 'config_value' => '15000']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'min_deposit_to_activate', 'config_value' => '20000']);
    }

    public function test_major_list_can_be_changed_and_is_used_for_class_suggestions(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put(route('admin.config.update'), [
                'bank_name' => 'Bank Mini Sekolah',
                'minimum_balance' => 10000,
                'min_deposit_to_activate' => 10000,
                'class_majors' => 'RPL, TKJ, MP',
            ])
            ->assertRedirect(route('admin.config.edit'));

        $this->assertDatabaseHas('system_configs', ['config_key' => 'class_majors', 'config_value' => 'RPL, TKJ, MP']);
        $this->assertSame(['RPL', 'TKJ', 'MP'], ClassRoom::majors());

        $suggestions = ClassRoom::suggestions();

        $this->assertContains('X MP 1', $suggestions);
        $this->assertNotContains('X DKV 1', $suggestions);
    }

    public function test_configuration_page_shows_the_official_major_list(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.config.edit'))
            ->assertOk()
            ->assertSee('RPL, TKJ, DKV, AK, LPS, MP, BR, BD');
    }

    public function test_configuration_requires_bank_name(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put(route('admin.config.update'), [
                'minimum_balance' => 10000,
            ])
            ->assertSessionHasErrors('bank_name');
    }

    public function test_teller_cannot_update_configuration(): void
    {
        $this->actingAsTeller()
            ->put(route('admin.config.update'), [
                'bank_name' => 'Hack',
                'minimum_balance' => 0,
            ])
            ->assertRedirect(route('login'));
    }
}
