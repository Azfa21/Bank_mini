<?php

namespace Tests\Feature\Admin;

use Tests\Feature\Teller\TellerTestCase;

class SystemConfigTest extends TellerTestCase
{
    public function test_admin_updates_all_configuration_keys(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put(route('admin.config.update'), [
                'bank_name' => 'Bank Mini SMK Nusantara',
                'operational_hours' => '08.00 - 15.00 WIB',
                'minimum_balance' => 15000,
                'admin_fee' => 2500,
                'max_withdrawal_without_order' => 500000,
                'min_deposit_to_activate' => 20000,
            ])
            ->assertRedirect(route('admin.config.edit'));

        $this->assertDatabaseHas('system_configs', ['config_key' => 'bank_name', 'config_value' => 'Bank Mini SMK Nusantara']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'operational_hours', 'config_value' => '08.00 - 15.00 WIB']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'minimum_balance', 'config_value' => '15000']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'admin_fee', 'config_value' => '2500']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'max_withdrawal_without_order', 'config_value' => '500000']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'min_deposit_to_activate', 'config_value' => '20000']);
    }

    public function test_configuration_requires_bank_name(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put(route('admin.config.update'), [
                'minimum_balance' => 10000,
                'admin_fee' => 0,
                'max_withdrawal_without_order' => 0,
            ])
            ->assertSessionHasErrors('bank_name');
    }

    public function test_teller_cannot_update_configuration(): void
    {
        $this->actingAsTeller()
            ->put(route('admin.config.update'), [
                'bank_name' => 'Hack',
                'minimum_balance' => 0,
                'admin_fee' => 0,
                'max_withdrawal_without_order' => 0,
            ])
            ->assertRedirect(route('login'));
    }
}
