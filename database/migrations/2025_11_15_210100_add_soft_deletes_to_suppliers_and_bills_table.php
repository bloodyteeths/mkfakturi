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
        // Add soft deletes to suppliers if missing
        if (Schema::hasTable('suppliers') && ! Schema::hasColumn('suppliers', 'deleted_at')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to bills if missing
        if (Schema::hasTable('bills') && ! Schema::hasColumn('bills', 'deleted_at')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite we avoid dropping columns to keep things simple in tests.
        if (config('database.default') !== 'sqlite') {
            if (Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'deleted_at')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->dropColumn('deleted_at');
                });
            }

            if (Schema::hasTable('bills') && Schema::hasColumn('bills', 'deleted_at')) {
                Schema::table('bills', function (Blueprint $table) {
                    $table->dropColumn('deleted_at');
                });
            }
        }
    }
};
