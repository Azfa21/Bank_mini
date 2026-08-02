<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('slip_number', 30)->nullable()->after('teller_id');
            $table->unsignedBigInteger('slip_amount')->nullable()->after('slip_number');
            $table->unsignedBigInteger('physical_cash_amount')->nullable()->after('slip_amount');
            $table->enum('status', ['pending', 'success', 'rejected'])->default('pending')->after('balance_after');
            $table->timestamp('transaction_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'slip_number',
                'slip_amount',
                'physical_cash_amount',
                'status',
                'transaction_at',
            ]);
        });
    }
};
