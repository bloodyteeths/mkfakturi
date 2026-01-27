<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make invitee_company_id nullable in company_referrals.
     * The column must be nullable because invitee hasn't signed up yet when referral is created.
     */
    public function up(): void
    {
        if (! Schema::hasTable('company_referrals')) {
            return;
        }

        // Drop foreign key first (required before modifying column)
        Schema::table('company_referrals', function (Blueprint $table) {
            // Try to drop the foreign key - it might have different names
            try {
                $table->dropForeign(['invitee_company_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist or have different name
            }
        });

        // Modify column to be nullable
        Schema::table('company_referrals', function (Blueprint $table) {
            $table->unsignedInteger('invitee_company_id')->nullable()->change();
        });

        // Re-add foreign key with nullable support
        Schema::table('company_referrals', function (Blueprint $table) {
            $table->foreign('invitee_company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't reverse - keep nullable
    }
};

// CLAUDE-CHECKPOINT
