<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->string('purpose', 16)->default('password')->after('account_id');
            $table->index(['account_type', 'account_id', 'purpose'], 'password_reset_otps_account_purpose_index');
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->dropIndex('password_reset_otps_account_purpose_index');
            $table->dropColumn('purpose');
        });
    }
};
