<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_akuntansi', function (Blueprint $table) {
            $table->string('journal_number', 30)->nullable()->after('transaction_id');
            $table->unsignedBigInteger('debit_amount')->default(0)->after('amount');
            $table->unsignedBigInteger('credit_amount')->default(0)->after('debit_amount');
            $table->string('description', 255)->nullable()->after('credit_amount');
            $table->enum('status', ['pending', 'posted'])->default('pending')->after('description');
            $table->timestamp('journal_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_akuntansi', function (Blueprint $table) {
            $table->dropColumn([
                'journal_number',
                'debit_amount',
                'credit_amount',
                'description',
                'status',
                'journal_at',
            ]);
        });
    }
};
