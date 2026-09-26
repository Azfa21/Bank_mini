<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saldo laci Teller dapat bernilai negatif (mis. penarikan sebelum ada
        // setoran), sehingga kolom saldo harus bertipe signed.
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->bigInteger('opening_balance')->change();
            $table->bigInteger('closing_balance_system')->change();
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('opening_balance')->change();
            $table->unsignedBigInteger('closing_balance_system')->change();
        });
    }
};
