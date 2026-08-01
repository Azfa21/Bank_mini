<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 20)->unique();
            $table->unsignedBigInteger('customer_id')->unique();
            $table->unsignedBigInteger('balance')->default(0);
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamp('opened_at')->useCurrent();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
