<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_akuntansi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('account_code', 10);
            $table->enum('entry_type', ['debit', 'credit']);
            $table->unsignedBigInteger('amount');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->onDelete('restrict');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE jurnal_akuntansi ADD CONSTRAINT chk_jurnal_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_akuntansi');
    }
};
