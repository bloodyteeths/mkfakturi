<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Expand project status enum to include more statuses.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL requires ALTER TABLE to change enum values
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('open', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any rows with new statuses to 'open'
        DB::table('projects')->whereIn('status', ['in_progress', 'completed', 'cancelled'])->update(['status' => 'open']);

        // Then revert the enum
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('open', 'closed', 'on_hold') DEFAULT 'open'");
    }
};
