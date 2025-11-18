<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'amount',
        'status',
        'payout_date',
        'payment_method',
        'payment_reference',
        'details',
        'notes',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payout_date' => 'date',
        'details' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the partner receiving this payout
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the user who processed this payout
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the affiliate events included in this payout
     */
    public function events(): HasMany
    {
        return $this->hasMany(AffiliateEvent::class);
    }

    /**
     * Scope to pending payouts
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to completed payouts
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to payouts for a specific partner
     */
    public function scopeForPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * Mark payout as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark payout as completed
     */
    public function markAsCompleted(string $paymentReference, ?int $processedBy = null): void
    {
        $this->update([
            'status' => 'completed',
            'payment_reference' => $paymentReference,
            'processed_at' => now(),
            'processed_by' => $processedBy ?? auth()->id(),
        ]);
    }

    /**
     * Mark payout as failed
     */
    public function markAsFailed(?string $reason = null): void
    {
        $details = $this->details ?? [];
        $details['failure_reason'] = $reason;
        $details['failed_at'] = now()->toIso8601String();

        $this->update([
            'status' => 'failed',
            'details' => $details,
        ]);
    }

    /**
     * Cancel the payout
     */
    public function cancel(?string $reason = null): void
    {
        $details = $this->details ?? [];
        $details['cancellation_reason'] = $reason;
        $details['cancelled_at'] = now()->toIso8601String();

        $this->update([
            'status' => 'cancelled',
            'details' => $details,
        ]);

        // Release associated events
        $this->events()->update([
            'paid_at' => null,
            'payout_id' => null,
        ]);
    }

    /**
     * Get event breakdown for this payout
     */
    public function getEventBreakdownAttribute(): array
    {
        $events = $this->events()
            ->selectRaw('event_type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('event_type')
            ->get();

        return $events->mapWithKeys(function ($event) {
            return [$event->event_type => [
                'count' => $event->count,
                'total' => $event->total,
            ]];
        })->toArray();
    }
}

// CLAUDE-CHECKPOINT
