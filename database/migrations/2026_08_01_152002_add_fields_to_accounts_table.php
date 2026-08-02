<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('academic_year', 20)->nullable()->after('customer_id');
            $table->unsignedBigInteger('opening_balance')->default(0)->after('academic_year');
            $table->unsignedBigInteger('minimum_balance')->default(10000)->after('opening_balance');
            $table->timestamp('closed_at')->nullable()->after('opened_at');
            $table->timestamps();
        });

        DB::table('accounts')->update([
            'opening_balance' => DB::raw('balance'),
        ]);
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'academic_year',
                'opening_balance',
                'minimum_balance',
                'closed_at',
                'created_at',
                'updated_at',
            ]);
        });
    }
};
