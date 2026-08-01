<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('system_configs')->updateOrInsert(
            ['config_key' => 'minimum_balance'],
            [
                'config_value' => '10000',
                'updated_at' => now(),
            ]
        );
    }
}
