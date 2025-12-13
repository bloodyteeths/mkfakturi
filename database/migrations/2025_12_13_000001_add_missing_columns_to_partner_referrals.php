<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add any missing columns to partner_referrals for legacy databases.
     */
    public function up(): void
    {
        if (! Schema::hasTable('partner_referrals')) {
            return;
        }

        Schema::table('partner_referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('partner_referrals', 'referral_token')) {
                $table->string('referral_token', 64)->unique()->after('invitee_email');
                $table->index('referral_token');
            }

            if (! Schema::hasColumn('partner_referrals', 'status')) {
                $table->string('status')->default('pending')->after('referral_token');
            }

            if (! Schema::hasColumn('partner_referrals', 'invited_at')) {
                $table->timestamp('invited_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('partner_referrals', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('invited_at');
            }

            if (! Schema::hasColumn('partner_referrals', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('accepted_at');
            }

            if (! Schema::hasColumn('partner_referrals', 'invitee_email')) {
                $table->string('invitee_email')->nullable()->after('invitee_partner_id');
                $table->index('invitee_email');
            }
        });
    }

    /**
     * Rollback missing columns addition.
     */
    public function down(): void
    {
        if (! Schema::hasTable('partner_referrals')) {
            return;
        }

        Schema::table('partner_referrals', function (Blueprint $table) {
            if (Schema::hasColumn('partner_referrals', 'referral_token')) {
                $table->dropUnique(['referral_token']);
                $table->dropIndex(['referral_token']);
                $table->dropColumn('referral_token');
            }

            if (Schema::hasColumn('partner_referrals', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('partner_referrals', 'invited_at')) {
                $table->dropColumn('invited_at');
            }

            if (Schema::hasColumn('partner_referrals', 'accepted_at')) {
                $table->dropColumn('accepted_at');
            }

            if (Schema::hasColumn('partner_referrals', 'declined_at')) {
                $table->dropColumn('declined_at');
            }

            if (Schema::hasColumn('partner_referrals', 'invitee_email')) {
                $table->dropIndex(['invitee_email']);
                $table->dropColumn('invitee_email');
            }
        });
    }
};

