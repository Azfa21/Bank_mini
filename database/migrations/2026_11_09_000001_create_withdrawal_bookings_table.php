<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->date('withdrawal_date');
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->timestamps();

            $table->index(['account_id', 'withdrawal_date', 'status'], 'withdrawal_bookings_account_date_status_idx');
            $table->index(['status', 'withdrawal_date'], 'withdrawal_bookings_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_bookings');
    }
};
