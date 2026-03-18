<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Migrate existing 'plus' partner tier to 'start'.
     */
    public function up(): void
    {
        $count = DB::table('users')
            ->where('partner_subscription_tier', 'plus')
            ->count();

        if ($count > 0) {
            DB::table('users')
                ->where('partner_subscription_tier', 'plus')
                ->update(['partner_subscription_tier' => 'start']);

            Log::info("Migrated {$count} partner(s) from 'plus' to 'start' tier");
        }
    }

    /**
     * Reverse: convert 'start' back to 'plus'.
     */
    public function down(): void
    {
        // Only revert users that were originally 'plus'
        // Since new signups also get 'start', this is a best-effort rollback
        DB::table('users')
            ->where('partner_subscription_tier', 'start')
            ->whereNull('stripe_subscription_id')
            ->update(['partner_subscription_tier' => 'plus']);
    }
};
// CLAUDE-CHECKPOINT
