<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicate = DB::table('transactions')
            ->whereNotNull('slip_number')
            ->select('slip_number', DB::raw('COUNT(*) as occurrence'))
            ->groupBy('slip_number')
            ->having('occurrence', '>', 1)
            ->exists();

        if ($hasDuplicate) {
            throw new RuntimeException('Duplicate transaction slip numbers must be reconciled before enforcing uniqueness.');
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('slip_number');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['slip_number']);
        });
    }
};
