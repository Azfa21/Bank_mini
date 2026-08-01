<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teller_id');
            $table->date('report_date');
            $table->unsignedBigInteger('opening_balance');
            $table->unsignedBigInteger('total_deposit')->default(0);
            $table->unsignedBigInteger('total_withdrawal')->default(0);
            $table->unsignedBigInteger('closing_balance_system');
            $table->unsignedBigInteger('closing_balance_physical')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['teller_id', 'report_date']);

            $table->foreign('teller_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
