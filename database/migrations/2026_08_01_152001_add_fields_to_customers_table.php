<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('class_name', 30)->nullable()->after('full_name');
            $table->string('phone_number', 20)->nullable()->after('class_name');
            $table->string('qr_code_value', 100)->unique()->nullable()->after('phone_number');
        });

        DB::table('customers')->whereRaw('qr_code_value IS NULL')->update([
            'qr_code_value' => DB::raw('qr_token'),
        ]);

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex('customers_qr_token_unique');
            $table->dropColumn('qr_token');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('qr_token', 100)->unique()->nullable()->after('full_name');
        });

        DB::table('customers')->whereRaw('qr_token IS NULL')->update([
            'qr_token' => DB::raw('qr_code_value'),
        ]);

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['class_name', 'phone_number', 'qr_code_value']);
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }
};
