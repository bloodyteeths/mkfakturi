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
            $table->unsignedBigInteger('invitee_partner_id')->nullable(); // Downline partner (nullable until signup)
            $table->foreign('invitee_partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->string('invitee_email')->nullable(); // Email before partner signs up
            $table->string('referral_token', 64)->unique(); // Unique token for signup link
            $table->string('status')->default('pending'); // pending, accepted, declined
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['invitee_partner_id', 'status']);
            $table->index(['inviter_partner_id', 'status']);
            $table->index('invitee_email');
            $table->index('referral_token');
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
