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
            $table->unsignedBigInteger('admin_fee')->default(0)->after('amount');
            $table->json('denomination_breakdown')->nullable()->after('physical_cash_amount');
            $table->unsignedBigInteger('reversal_of_transaction_id')->nullable()->after('notes');
            $table->timestamp('reversed_at')->nullable()->after('reversal_of_transaction_id');
            $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
            $table->string('reversal_reason', 255)->nullable()->after('reversed_by');

            $table->foreign('reversal_of_transaction_id')
                ->references('id')
                ->on('transactions')
                ->onDelete('restrict');

            $table->foreign('reversed_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->json('closing_breakdown')->nullable()->after('closing_balance_physical');
            $table->unsignedBigInteger('total_admin_fee')->default(0)->after('total_withdrawal');
        });

        $defaults = [
            'bank_name' => 'Bank Mini Sekolah',
            'operational_hours' => '07.30 - 14.00 WIB',
            'max_withdrawal_without_order' => '0',
            'admin_fee' => '0',
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

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn(['closing_breakdown', 'total_admin_fee']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['reversal_of_transaction_id']);
            $table->dropForeign(['reversed_by']);
            $table->dropColumn([
                'admin_fee',
                'denomination_breakdown',
                'reversal_of_transaction_id',
                'reversed_at',
                'reversed_by',
                'reversal_reason',
            ]);
        });
    }
};
