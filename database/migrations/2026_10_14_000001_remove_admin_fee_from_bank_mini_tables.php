<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('admin_fee');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn('total_admin_fee');
        });

        DB::table('system_configs')->where('config_key', 'admin_fee')->delete();
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_fee')->default(0)->after('amount');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->bigInteger('total_admin_fee')->default(0)->after('total_withdrawal');
        });

        if (! DB::table('system_configs')->where('config_key', 'admin_fee')->exists()) {
            DB::table('system_configs')->insert([
                'config_key' => 'admin_fee',
                'config_value' => '0',
            ]);
        }
    }
};
