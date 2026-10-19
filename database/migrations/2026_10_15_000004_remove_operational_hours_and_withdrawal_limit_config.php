<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_configs')
            ->whereIn('config_key', ['operational_hours', 'max_withdrawal_without_order'])
            ->delete();
    }

    public function down(): void
    {
        $defaults = [
            'operational_hours' => '07.30 - 14.00 WIB',
            'max_withdrawal_without_order' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (! DB::table('system_configs')->where('config_key', $key)->exists()) {
                DB::table('system_configs')->insert([
                    'config_key' => $key,
                    'config_value' => $value,
                ]);
            }
        }
    }
};
