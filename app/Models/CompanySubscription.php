<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanySubscription Model
 *
 * Represents a company's subscription to a Facturino plan
 * This is the revenue source - companies pay for subscriptions
 */
class CompanySubscription extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'company_id',
        'accountant_id',
        'plan',
        'provider',
        'provider_subscription_id',
        'price_monthly',
        'status',
        'started_at',
        'trial_ends_at',
        'canceled_at',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'started_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    /**
     * Company that owns this subscription
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Accountant who referred this company (for commission tracking)
     */
    public function accountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_id');
    }

    /**
     * Scope: Only active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Only trial subscriptions
     */
    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    /**
     * Scope: Canceled subscriptions
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    /**
     * Scope: Past due subscriptions
     */
    public function scopePastDue($query)
    {
        return $query->where('status', 'past_due');
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active']);
    }

    /**
     * Check if subscription is on trial
     */
    public function onTrial(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if trial has expired
     */
    public function trialExpired(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isPast();
    }

    /**
     * Cancel the subscription
     */
    public function cancel(): bool
    {
        $this->status = 'canceled';
        $this->canceled_at = Carbon::now();

        return $this->save();
    }

    /**
     * Pause the subscription
     */
    public function pause(): bool
    {
        $this->status = 'paused';

        return $this->save();
    }

    /**
     * Resume a paused subscription
     */
    public function resume(): bool
    {
        if ($this->status !== 'paused') {
            return false;
        }

        $this->status = 'active';

        return $this->save();
    }

    /**
     * Swap to a different plan
     */
    public function swap(string $newPlan, float $newPrice): bool
    {
        $this->plan = $newPlan;
        $this->price_monthly = $newPrice;

        return $this->save();
    }

    /**
     * Mark subscription as active (when trial converts or payment succeeds)
     */
    public function activate(): bool
    {
        $this->status = 'active';

        if (!$this->started_at) {
            $this->started_at = Carbon::now();
        }

        return $this->save();
    }

    /**
     * Mark subscription as past due (when payment fails)
     */
    public function markPastDue(): bool
    {
        $this->status = 'past_due';

        return $this->save();
    }

    /**
     * Get plan display name
     */
    public function getPlanDisplayNameAttribute(): string
    {
        $names = [
            'free' => 'Free',
            'starter' => 'Starter',
            'standard' => 'Standard',
            'business' => 'Business',
            'max' => 'Max',
        ];

        return $names[$this->plan] ?? ucfirst($this->plan);
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayNameAttribute(): string
    {
        $names = [
            'trial' => 'Trial',
            'active' => 'Active',
            'past_due' => 'Past Due',
            'paused' => 'Paused',
            'canceled' => 'Canceled',
        ];

        return $names[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get days remaining in trial
     */
    public function getTrialDaysRemainingAttribute(): ?int
    {
        if ($this->status !== 'trial' || !$this->trial_ends_at) {
            return null;
        }

        $days = Carbon::now()->diffInDays($this->trial_ends_at, false);

        return max(0, (int) $days);
    }
}
// CLAUDE-CHECKPOINT
