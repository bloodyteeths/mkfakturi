<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_partner_id',
        'upline_partner_id',
        'company_id',
        'event_type',
        'amount',
        'upline_amount',
        'month_ref',
        'subscription_id',
        'is_clawed_back',
        'paid_at',
        'payout_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'upline_amount' => 'decimal:2',
        'is_clawed_back' => 'boolean',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the partner who earns this commission
     */
    public function affiliatePartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'affiliate_partner_id');
    }

    /**
     * Get the upline partner (if multi-level)
     */
    public function uplinePartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'upline_partner_id');
    }

    /**
     * Get the company this event relates to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the payout this event is included in
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    /**
     * Scope to unpaid events
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNull('paid_at')
            ->where('is_clawed_back', false);
    }

    /**
     * Scope to paid events
     */
    public function scopePaid($query)
    {
        return $query->whereNotNull('paid_at');
    }

    /**
     * Scope to events for a specific partner
     */
    public function scopeForPartner($query, $partnerId)
    {
        return $query->where('affiliate_partner_id', $partnerId);
    }

    /**
     * Scope to events for a specific month
     */
    public function scopeForMonth($query, string $monthRef)
    {
        return $query->where('month_ref', $monthRef);
    }

    /**
     * Scope to recurring commission events
     */
    public function scopeRecurringCommissions($query)
    {
        return $query->where('event_type', 'recurring_commission');
    }

    /**
     * Scope to bounty events
     */
    public function scopeBounties($query)
    {
        return $query->whereIn('event_type', ['company_bounty', 'partner_bounty']);
    }

    /**
     * Mark event as paid and associate with payout
     */
    public function markAsPaid(Payout $payout): void
    {
        $this->update([
            'paid_at' => now(),
            'payout_id' => $payout->id,
        ]);
    }

    /**
     * Mark event as clawed back
     */
    public function clawback(string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['clawback_reason'] = $reason;
        $metadata['clawed_back_at'] = now()->toIso8601String();

        $this->update([
            'is_clawed_back' => true,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get the total commission amount (including upline if applicable)
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->amount + ($this->upline_amount ?? 0);
    }
}

// CLAUDE-CHECKPOINT
