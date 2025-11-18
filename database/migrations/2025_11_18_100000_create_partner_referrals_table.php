<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Partner→Partner referral tracking for multi-level commissions (AC-15, AC-18)
     */
    public function up(): void
    {
        Schema::create('partner_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inviter_partner_id'); // Upline partner
            $table->foreign('inviter_partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->unsignedBigInteger('invitee_partner_id'); // Downline partner
            $table->foreign('invitee_partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, accepted, declined
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();

            // Ensure unique inviter-invitee combinations
            $table->unique(['inviter_partner_id', 'invitee_partner_id'], 'partner_referral_unique');

            // Indexes for performance
            $table->index(['invitee_partner_id', 'status']);
            $table->index(['inviter_partner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_referrals');
    }
};

// CLAUDE-CHECKPOINT
