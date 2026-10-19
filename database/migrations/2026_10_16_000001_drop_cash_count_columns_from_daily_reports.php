<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Loket tidak lagi menghitung uang fisik di laci saat penutupan kas.
        // Laporan penutupan memakai saldo akhir sistem, jadi kolom hitung fisik,
        // rincian pecahan, dan selisihnya tidak dipakai lagi.
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn('closing_balance_physical');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn('closing_breakdown');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn('cash_difference');
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('closing_balance_physical')->nullable()->after('closing_balance_system');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->json('closing_breakdown')->nullable()->after('closing_balance_physical');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->bigInteger('cash_difference')->nullable()->after('closing_breakdown');
        });
    }
};
