<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savers', function (Blueprint $table) {
            // Nullable sampai penabung membuat password sendiri lewat kode verifikasi
            // email; sebelum itu penabung belum boleh melalui gerbang login.
            $table->string('password_hash', 255)->nullable()->after('pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('savers', function (Blueprint $table) {
            $table->dropColumn('password_hash');
        });
    }
};
