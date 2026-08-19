<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedSmallInteger('failed_pin_attempts')->default(0)->after('status');
            $table->timestamp('locked_until')->nullable()->after('failed_pin_attempts');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('type', 20)->change();
            $table->unsignedBigInteger('recipient_account_id')->nullable()->after('account_id');
            $table->string('notes', 255)->nullable()->after('physical_cash_amount');

            $table->foreign('recipient_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('restrict');
        });

        Schema::table('jurnal_akuntansi', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->nullable()->change();
            $table->unsignedBigInteger('cash_transfer_id')->nullable()->after('transaction_id');

            $table->foreign('cash_transfer_id')
                ->references('id')
                ->on('cash_transfers')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_akuntansi', function (Blueprint $table) {
            $table->dropForeign(['cash_transfer_id']);
            $table->dropColumn('cash_transfer_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['recipient_account_id']);
            $table->dropColumn(['recipient_account_id', 'notes']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['failed_pin_attempts', 'locked_until']);
        });
    }
};
