<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'provider',
        'event_type',
        'event_id',
        'payload',
        'signature',
        'status',
        'processed_at',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Scope to filter by company
     */
    public function scopeWhereCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by provider
     */
    public function scopeWhereProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to filter pending events
     */
    public function scopeWherePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter failed events
     */
    public function scopeWhereFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Relationship to company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Mark event as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark event as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Check if event can be retried
     */
    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->status === 'failed' && $this->retry_count < $maxRetries;
    }
}
// CLAUDE-CHECKPOINT
