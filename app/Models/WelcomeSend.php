<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// CLAUDE-CHECKPOINT
class WelcomeSend extends Model
{
    protected $fillable = [
        'sendable_type',
        'sendable_id',
        'email',
        'template_key',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the parent sendable model (Company or Partner).
     */
    public function sendable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: only queued sends.
     */
    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    /**
     * Mark as sent.
     */
    public function markSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markFailed(): void
    {
        $this->update([
            'status' => 'failed',
        ]);
    }
}
