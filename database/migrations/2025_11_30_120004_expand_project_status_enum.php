<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Expand project status enum to include more statuses.
 * Note: SQLite doesn't support ENUM, so we use a string column with validation at the model level.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, modify the enum values
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('open', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'open'");
        }
        // SQLite: status is already a string column, no schema change needed
        // Validation is handled at the model level
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any rows with new statuses to 'open'
        DB::table('projects')->whereIn('status', ['in_progress', 'completed', 'cancelled'])->update(['status' => 'open']);

        // For MySQL, revert the enum
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('open', 'closed', 'on_hold') DEFAULT 'open'");
        }
    }
};
