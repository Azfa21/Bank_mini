<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('student_number', 30)->unique();
            $table->string('full_name', 100);
            $table->string('pin_hash', 255);
            $table->string('qr_token', 100)->unique();
            $table->unsignedBigInteger('created_by');
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
