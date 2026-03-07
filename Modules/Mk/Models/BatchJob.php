<?php

namespace Modules\Mk\Models;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchJob extends Model
{
    use HasFactory;

    protected $table = 'batch_jobs';

    protected $fillable = [
        'partner_id',
        'operation_type',
        'company_ids',
        'parameters',
        'status',
        'total_items',
        'completed_items',
        'failed_items',
        'results',
        'error_log',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'company_ids' => 'array',
            'parameters' => 'array',
            'results' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_items' => 'integer',
            'completed_items' => 'integer',
            'failed_items' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    // ---- Scopes ----

    /**
     * Scope to a specific partner.
     */
    public function scopeForPartner($query, int $partnerId)
    {
        return $query->where('batch_jobs.partner_id', $partnerId);
    }

    /**
     * Scope by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('batch_jobs.status', $status);
    }

    /**
     * Scope by operation type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('batch_jobs.operation_type', $type);
    }

    // ---- Accessors ----

    /**
     * Get progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_items === 0) {
            return 0;
        }

        return round(($this->completed_items + $this->failed_items) / $this->total_items * 100, 1);
    }

    // ---- Helper Methods ----

    /**
     * Mark job as running.
     */
    public function markRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark job as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark job as failed.
     */
    public function markFailed(?string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_log' => $errorMessage,
        ]);
    }

    /**
     * Increment completed items count.
     */
    public function incrementCompleted(): void
    {
        $this->increment('completed_items');
        $this->refresh();
    }

    /**
     * Increment failed items count.
     */
    public function incrementFailed(): void
    {
        $this->increment('failed_items');
        $this->refresh();
    }

    /**
     * Add a per-company result entry.
     */
    public function addResult(int $companyId, string $status, string $message, ?string $filePath = null): void
    {
        $results = $this->results ?? [];

        $entry = [
            'company_id' => $companyId,
            'status' => $status,
            'message' => $message,
        ];

        if ($filePath !== null) {
            $entry['file_path'] = $filePath;
        }

        $results[] = $entry;

        $this->update(['results' => $results]);
    }
}

// CLAUDE-CHECKPOINT
