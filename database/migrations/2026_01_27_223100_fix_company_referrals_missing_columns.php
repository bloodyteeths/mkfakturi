<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix missing columns in company_referrals table.
     * This migration is idempotent - safe to run multiple times.
     */
    public function up(): void
    {
        // First, ensure the table exists
        if (! Schema::hasTable('company_referrals')) {
            Schema::create('company_referrals', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('inviter_company_id');
                $table->foreign('inviter_company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->unsignedInteger('invitee_company_id')->nullable();
                $table->foreign('invitee_company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->string('invitee_email');
                $table->string('referral_token', 64)->unique();
                $table->string('status')->default('pending');
                $table->text('message')->nullable();
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('declined_at')->nullable();
                $table->timestamps();

                $table->index(['invitee_company_id', 'status']);
                $table->index(['inviter_company_id', 'status']);
                $table->index('invitee_email');
                $table->index('referral_token');
            });

            return;
        }

        // Add missing columns if table exists
        Schema::table('company_referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('company_referrals', 'referral_token')) {
                $table->string('referral_token', 64)->nullable()->after('invitee_email');
            }

            if (! Schema::hasColumn('company_referrals', 'message')) {
                $table->text('message')->nullable()->after('status');
            }

            if (! Schema::hasColumn('company_referrals', 'invited_at')) {
                $table->timestamp('invited_at')->nullable()->after('message');
            }

            if (! Schema::hasColumn('company_referrals', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('invited_at');
            }

            if (! Schema::hasColumn('company_referrals', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('accepted_at');
            }
        });

        // Add index on referral_token if column was just added
        if (Schema::hasColumn('company_referrals', 'referral_token')) {
            try {
                Schema::table('company_referrals', function (Blueprint $table) {
                    $table->index('referral_token', 'company_referrals_referral_token_index');
                });
            } catch (\Exception $e) {
                // Index might already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table - just leave it as is
    }
};

// CLAUDE-CHECKPOINT
