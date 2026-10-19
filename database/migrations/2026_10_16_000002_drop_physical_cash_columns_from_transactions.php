<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nominal transaksi kini identik dengan nominal yang diisi teller, jadi uang
     * fisik tidak perlu dihitung ulang dan rincian pecahan tidak lagi dikirim
     * form. Kedua kolom dihapus agar tidak ada data mati.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('physical_cash_amount');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('denomination_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('physical_cash_amount')->nullable()->after('slip_amount');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->json('denomination_breakdown')->nullable()->after('physical_cash_amount');
        });
    }
};
