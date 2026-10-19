<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savers', function (Blueprint $table) {
            // Alumni tidak dihapus, hanya dipindahkan ke status "graduated" supaya
            // riwayat transaksi dan rekeningnya tetap utuh.
            $table->enum('status', ['active', 'blocked', 'graduated'])->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('savers', function (Blueprint $table) {
            $table->enum('status', ['active', 'blocked'])->default('active')->change();
        });
    }
};
