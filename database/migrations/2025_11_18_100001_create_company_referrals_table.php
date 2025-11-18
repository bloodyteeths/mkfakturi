<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Company→Company referral tracking (AC-14, AC-17)
     */
    public function up(): void
    {
        Schema::create('company_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('inviter_company_id'); // Company making the referral
            $table->foreign('inviter_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unsignedInteger('invitee_company_id'); // Company being referred
            $table->foreign('invitee_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('invitee_email')->nullable(); // Email before signup
            $table->string('status')->default('pending'); // pending, accepted, declined
            $table->text('message')->nullable(); // Optional referral message
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();

            // Ensure unique inviter-invitee combinations
            $table->unique(['inviter_company_id', 'invitee_company_id'], 'company_referral_unique');

            // Indexes for performance
            $table->index(['invitee_company_id', 'status']);
            $table->index(['inviter_company_id', 'status']);
            $table->index('invitee_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_referrals');
    }
};

// CLAUDE-CHECKPOINT
