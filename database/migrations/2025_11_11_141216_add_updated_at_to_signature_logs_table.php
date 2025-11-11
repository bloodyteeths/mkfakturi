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
        // Add updated_at column if table exists but column doesn't
        if (Schema::hasTable('signature_logs') && !Schema::hasColumn('signature_logs', 'updated_at')) {
            Schema::table('signature_logs', function (Blueprint $table) {
                // Add updated_at column after created_at
                $table->timestamp('updated_at')->nullable()->after('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('signature_logs', 'updated_at')) {
            Schema::table('signature_logs', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }
    }
};
