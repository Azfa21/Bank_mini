<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['status', 'type', 'transaction_at'], 'idx_transactions_status_type_date');
        });

        Schema::table('jurnal_akuntansi', function (Blueprint $table) {
            $table->index(['account_code', 'journal_at'], 'idx_jurnal_account_code_date');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['entity_name', 'entity_id'], 'idx_audit_logs_entity');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_status_type_date');
        });

        Schema::table('jurnal_akuntansi', function (Blueprint $table) {
            $table->dropIndex('idx_jurnal_account_code_date');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_entity');
        });
    }
};
