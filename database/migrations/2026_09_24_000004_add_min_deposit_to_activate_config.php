<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('system_configs')->where('config_key', 'min_deposit_to_activate')->exists()) {
            DB::table('system_configs')->insert([
                'config_key' => 'min_deposit_to_activate',
                'config_value' => '10000',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_configs')->where('config_key', 'min_deposit_to_activate')->delete();
    }
};
