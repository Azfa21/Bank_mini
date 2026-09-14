<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->string('report_number', 30)->nullable()->unique()->after('teller_id');
            $table->bigInteger('cash_difference')->nullable()->after('closing_balance_physical');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->timestamp('submitted_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropUnique(['report_number']);
            $table->dropColumn([
                'report_number',
                'cash_difference',
                'approval_notes',
                'submitted_at',
            ]);
        });
    }
};
