<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penamaan "brankas utama (khazanah)" diganti menjadi "kas utama".
        // Deskripsi jurnal historis biarkan apa adanya; hanya data terstruktur yang disamakan.
        DB::table('cash_transfers')
            ->where('destination_location', 'Brankas Utama')
            ->update(['destination_location' => 'Kas Utama']);

        Schema::table('cash_transfers', function (Blueprint $table) {
            $table->string('destination_location', 50)->default('Kas Utama')->change();
        });
    }

    public function down(): void
    {
        Schema::table('cash_transfers', function (Blueprint $table) {
            $table->string('destination_location', 50)->default('Brankas Utama')->change();
        });

        DB::table('cash_transfers')
            ->where('destination_location', 'Kas Utama')
            ->update(['destination_location' => 'Brankas Utama']);
    }
};
