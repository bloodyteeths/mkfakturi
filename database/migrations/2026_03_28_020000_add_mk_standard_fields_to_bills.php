<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Macedonian standard accounting fields to bills table.
     *
     * - supply_date: Ден на промет per ЗДДВ Art. 53
     * - place_of_issue: Место на издавање
     * - payment_terms_days: Payment terms for auto-calculating due_date
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            if (! Schema::hasColumn('bills', 'supply_date')) {
                $table->date('supply_date')->nullable()->after('bill_date');
            }

            if (! Schema::hasColumn('bills', 'payment_terms_days')) {
                $table->unsignedInteger('payment_terms_days')->nullable()->after('due_date');
            }

            if (! Schema::hasColumn('bills', 'place_of_issue')) {
                $table->string('place_of_issue', 255)->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migration (non-destructive: columns are kept).
     */
    public function down(): void
    {
        // Non-destructive: do not drop columns to prevent data loss.
    }

    // CLAUDE-CHECKPOINT
};
