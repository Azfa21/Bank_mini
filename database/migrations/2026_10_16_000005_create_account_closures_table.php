<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_closures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('saver_id');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('payout_transaction_id')->nullable();
            $table->unsignedBigInteger('payout_amount')->default(0);
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('saver_id')->references('id')->on('savers')->onDelete('restrict');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('decided_by')->references('id')->on('users')->onDelete('restrict');

            $table->index('status');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_closures');
    }
};
