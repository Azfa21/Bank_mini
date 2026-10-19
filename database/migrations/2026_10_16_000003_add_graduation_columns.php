<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savers', function (Blueprint $table) {
            $table->timestamp('graduated_at')->nullable()->after('status');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('graduated_at')->nullable()->after('graduation_year');
        });
    }

    public function down(): void
    {
        Schema::table('savers', function (Blueprint $table) {
            $table->dropColumn('graduated_at');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('graduated_at');
        });
    }
};
