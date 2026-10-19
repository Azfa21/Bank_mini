<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->after('account_type');
        });

        // Kode OTP dicocokkan ke akun, bukan ke alamat email: alamat email bisa
        // diganti kapan saja tanpa mengulang permintaan, sedangkan id akun tidak.
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->dropIndex(['account_type', 'email']);
        });

        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->index(['account_type', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->dropIndex(['account_type', 'account_id']);
        });

        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->index(['account_type', 'email']);
            $table->dropColumn('account_id');
        });
    }
};
