<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure invitee_partner_id is nullable to allow pending invites without invitee partner.
     */
    public function up(): void
    {
        if (! Schema::hasTable('partner_referrals')) {
            return;
        }

        // Make invitee_partner_id nullable (MySQL requires raw SQL for change without DBAL)
        try {
            DB::statement('ALTER TABLE partner_referrals MODIFY invitee_partner_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Ignore if the column is already nullable or cannot be altered
        }
    }

    /**
     * No-op rollback (safe).
     */
    public function down(): void
    {
        // Intentionally left blank to avoid reintroducing NOT NULL
    }
};

