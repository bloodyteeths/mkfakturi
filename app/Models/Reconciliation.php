<?php

namespace App\Models;

use App\Traits\HasAuditing;
use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reconciliation Model
 *
 * Tracks the matching of bank transactions with invoices.
 * Supports automatic, suggested, and manual reconciliation workflows.
 *
 * Confidence Score Buckets:
 * - ≥0.9: Auto-matched (automatically reconciled)
 * - 0.5-0.9: Suggested matches (require user approval)
 * - <0.5: Manual reconciliation needed
 */
class Reconciliation extends Model
{
    use HasAuditing;
    use HasFactory;
    use TenantScope;

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_AUTO_MATCHED = 'auto_matched';

    /**
     * Confidence score thresholds
     */
    const THRESHOLD_AUTO_MATCH = 0.9; // ≥0.9: Auto-match

    const THRESHOLD_SUGGEST = 0.5; // 0.5-0.9: Suggest
    // <0.5: Manual reconciliation

    protected $fillable = [
        'company_id',
        'bank_transaction_id',
        'invoice_id',
        'confidence_score',
        'status',
        'reconciled_by',
        'reconciled_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
        'reconciled_at' => 'datetime',
    ];

    protected $dates = [
        'reconciled_at',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Scopes
     */
    public function scopeWhereCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where('company_id', $companyId);
    }

    public function scopeWherePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAutoMatched($query)
    {
        return $query->where('status', self::STATUS_AUTO_MATCHED)
            ->where('confidence_score', '>=', self::THRESHOLD_AUTO_MATCH);
    }

    public function scopeSuggested($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('confidence_score', '>=', self::THRESHOLD_SUGGEST)
            ->where('confidence_score', '<', self::THRESHOLD_AUTO_MATCH);
    }

    public function scopeManual($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('confidence_score', '<', self::THRESHOLD_SUGGEST);
    }

    /**
     * Helper Methods
     */

    /**
     * Check if this reconciliation is auto-matched
     */
    public function isAutoMatched(): bool
    {
        return $this->status === self::STATUS_AUTO_MATCHED &&
               $this->confidence_score >= self::THRESHOLD_AUTO_MATCH;
    }

    /**
     * Check if this reconciliation is a suggested match
     */
    public function isSuggested(): bool
    {
        return $this->status === self::STATUS_PENDING &&
               $this->confidence_score >= self::THRESHOLD_SUGGEST &&
               $this->confidence_score < self::THRESHOLD_AUTO_MATCH;
    }

    /**
     * Check if this reconciliation needs manual review
     */
    public function needsManualReview(): bool
    {
        return $this->status === self::STATUS_PENDING &&
               $this->confidence_score < self::THRESHOLD_SUGGEST;
    }

    /**
     * Approve this reconciliation match
     *
     * @param  int|null  $userId  User who approved
     * @param  string|null  $notes  Optional approval notes
     */
    public function approve(?int $userId = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'reconciled_by' => $userId ?? auth()->id(),
            'reconciled_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Reject this reconciliation match
     *
     * @param  string  $reason  Rejection reason
     * @param  int|null  $userId  User who rejected
     */
    public function reject(string $reason, ?int $userId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'reconciled_by' => $userId ?? auth()->id(),
            'reconciled_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Get confidence percentage (0-100)
     */
    public function getConfidencePercentageAttribute(): float
    {
        return round($this->confidence_score * 100, 2);
    }

    /**
     * Get confidence category
     */
    public function getConfidenceCategoryAttribute(): string
    {
        if ($this->confidence_score >= self::THRESHOLD_AUTO_MATCH) {
            return 'auto';
        } elseif ($this->confidence_score >= self::THRESHOLD_SUGGEST) {
            return 'suggested';
        } else {
            return 'manual';
        }
    }
}

// CLAUDE-CHECKPOINT
