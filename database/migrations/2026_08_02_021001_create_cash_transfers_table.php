<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('supervisor_id');
            $table->unsignedBigInteger('amount');
            $table->string('source_location', 50)->default('Laci Teller');
            $table->string('destination_location', 50)->default('Brankas Utama');
            $table->enum('status', ['transferred'])->default('transferred');
            $table->timestamp('transferred_at')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('report_id');

            $table->foreign('report_id')
                ->references('id')
                ->on('daily_reports')
                ->onDelete('restrict');

            $table->foreign('supervisor_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transfers');
    }
};
