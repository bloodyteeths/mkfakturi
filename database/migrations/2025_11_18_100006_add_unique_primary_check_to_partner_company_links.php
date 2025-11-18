<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add database check constraint to prevent multiple primary partners per company
     */
    public function up(): void
    {
        // MySQL doesn't support filtered unique indexes, so we'll add a check constraint
        // This prevents multiple is_primary=true records for the same company_id
        if (DB::getDriverName() === 'mysql') {
            // MySQL 8.0.16+ supports check constraints
            DB::statement('
                ALTER TABLE partner_company_links
                ADD CONSTRAINT chk_single_primary_per_company
                CHECK (
                    is_primary = FALSE OR
                    (SELECT COUNT(*) FROM partner_company_links AS pcl2
                     WHERE pcl2.company_id = partner_company_links.company_id
                     AND pcl2.is_primary = TRUE) <= 1
                )
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE partner_company_links DROP CONSTRAINT IF EXISTS chk_single_primary_per_company');
        }
    }
};

// CLAUDE-CHECKPOINT
