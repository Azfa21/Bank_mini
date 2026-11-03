<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kas yang cair dari kas utama ke laci (permintaan kas teller yang disetujui)
        // ikut menghitung saldo laci, jadi laporan harian perlu baris mutasinya sendiri.
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('total_cash_received')->default(0)->after('total_deposit');
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn('total_cash_received');
        });
    }
};
