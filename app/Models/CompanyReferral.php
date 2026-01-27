<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanyReferral Model
 *
 * Tracks company-to-company referrals for the referral reward program.
 * When Company A invites Company B:
 * - Invitee (B) gets 10% off first payment
 * - Inviter (A) gets 10% off next billing cycle
 */
class CompanyReferral extends Model
{
    protected $table = 'company_referrals';

    protected $fillable = [
        'inviter_company_id',
        'invitee_company_id',
        'invitee_email',
        'referral_token',
        'status',
        'message',
        'invited_at',
        'accepted_at',
        'declined_at',
        'invitee_stripe_coupon_id',
        'inviter_stripe_coupon_id',
        'invitee_discount_applied_at',
        'inviter_discount_applied_at',
        'referral_reward_status',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'invitee_discount_applied_at' => 'datetime',
        'inviter_discount_applied_at' => 'datetime',
    ];

    /**
     * Get the company that made the referral.
     */
    public function inviterCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'inviter_company_id');
    }

    /**
     * Get the company that was referred.
     */
    public function inviteeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'invitee_company_id');
    }

    /**
     * Scope to find pending referrals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to find accepted referrals.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to find referrals awaiting inviter reward.
     */
    public function scopeAwaitingInviterReward($query)
    {
        return $query->where('referral_reward_status', 'invitee_rewarded');
    }

    /**
     * Check if this referral is eligible for rewards.
     */
    public function isEligibleForRewards(): bool
    {
        return $this->status === 'pending'
            && $this->referral_reward_status === 'pending';
    }

    /**
     * Mark invitee discount as applied.
     */
    public function markInviteeRewarded(string $couponId): void
    {
        $this->update([
            'invitee_stripe_coupon_id' => $couponId,
            'invitee_discount_applied_at' => now(),
            'referral_reward_status' => 'invitee_rewarded',
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * Mark inviter discount as applied.
     */
    public function markInviterRewarded(string $couponId): void
    {
        $this->update([
            'inviter_stripe_coupon_id' => $couponId,
            'inviter_discount_applied_at' => now(),
            'referral_reward_status' => 'both_rewarded',
        ]);
    }

    /**
     * Find referral by token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('referral_token', $token)->first();
    }
}

// CLAUDE-CHECKPOINT
