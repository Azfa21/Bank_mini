<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savers', function (Blueprint $table) {
            $table->string('member_nis', 30)->nullable()->after('student_number');
            $table->timestamp('deleted_at')->nullable()->after('status');
        });

        // Every existing saver becomes a member whose account is already active.
        DB::table('savers')->orderBy('id')->chunkById(200, function ($savers) {
            foreach ($savers as $saver) {
                if (! DB::table('members')->where('nis', $saver->student_number)->exists()) {
                    DB::table('members')->insert([
                        'nis' => $saver->student_number,
                        'full_name' => $saver->full_name,
                        'graduation_year' => null,
                        'status' => 'account_active',
                        'created_by' => $saver->created_by,
                        'created_at' => $saver->created_at ?? now(),
                        'updated_at' => $saver->created_at ?? now(),
                    ]);
                }

                DB::table('savers')
                    ->where('id', $saver->id)
                    ->update(['member_nis' => $saver->student_number]);
            }
        });

        Schema::table('savers', function (Blueprint $table) {
            $table->foreign('member_nis')
                ->references('nis')
                ->on('members')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('savers', function (Blueprint $table) {
            $table->dropForeign(['member_nis']);
            $table->dropColumn(['member_nis', 'deleted_at']);
        });
    }
};
