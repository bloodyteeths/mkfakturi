<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add column if table exists but column doesn't
        if (Schema::hasTable('signature_logs') && !Schema::hasColumn('signature_logs', 'company_id')) {
            Schema::table('signature_logs', function (Blueprint $table) {
                // Add company_id column after id
                $table->unsignedInteger('company_id')->after('id')->index();

                // Add foreign key constraint
                $table->foreign('company_id')
                      ->references('id')
                      ->on('companies')
                      ->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('signature_logs', 'company_id')) {
            Schema::table('signature_logs', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};
