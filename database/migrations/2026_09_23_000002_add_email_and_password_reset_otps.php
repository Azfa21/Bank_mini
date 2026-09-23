<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->unique()->after('username');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->unique()->after('phone_number');
        });

        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->enum('account_type', ['staff', 'nasabah']);
            $table->string('email', 255);
            $table->string('otp_hash', 255);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['account_type', 'email']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });
    }
};
