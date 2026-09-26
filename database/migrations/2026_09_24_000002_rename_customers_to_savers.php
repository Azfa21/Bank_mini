<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('customers', 'savers');

        Schema::table('accounts', function (Blueprint $table) {
            $table->renameColumn('customer_id', 'saver_id');
        });

        Schema::table('mobile_tokens', function (Blueprint $table) {
            $table->renameColumn('customer_id', 'saver_id');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_tokens', function (Blueprint $table) {
            $table->renameColumn('saver_id', 'customer_id');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->renameColumn('saver_id', 'customer_id');
        });

        Schema::rename('savers', 'customers');
    }
};
