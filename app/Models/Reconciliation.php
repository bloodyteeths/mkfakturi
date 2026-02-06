<?php

namespace App\Models;

use App\Traits\HasAuditing;
use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reconciliation Model
 *
 * Tracks the matching of bank transactions with invoices and payments.
 * Supports automatic, manual, and rule-based reconciliation workflows.
 *
 * Confidence Score Buckets (0-100 scale):
 * - >=90: Auto-matched (automatically reconciled)
 * - 50-89: Suggested matches (require user approval)
 * - <50: Manual reconciliation needed
 *
 * @property int $id
 * @property int $company_id
 * @property int $bank_transaction_id
 * @property int|null $invoice_id
 * @property int|null $payment_id
 * @property string $status
 * @property string $match_type
 * @property float|null $confidence
 * @property array|null $match_details
 * @property int|null $matched_by
 * @property \Carbon\Carbon|null $matched_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Reconciliation extends Model
{
    use HasAuditing;
    use HasFactory;
    use TenantScope;

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_MATCHED = 'matched';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_MANUAL = 'manual';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_SPLIT = 'split';

    // Legacy constants for backward compatibility
    public const STATUS_APPROVED = 'matched';

    public const STATUS_REJECTED = 'ignored';

    public const STATUS_AUTO_MATCHED = 'matched';

    /**
     * Match type constants
     */
    public const MATCH_TYPE_AUTO = 'auto';

    public const MATCH_TYPE_MANUAL = 'manual';

    public const MATCH_TYPE_RULE = 'rule';

    /**
     * Confidence score thresholds (0-100 scale)
     */
    public const THRESHOLD_AUTO_MATCH = 90.0; // >=90: Auto-match

    public const THRESHOLD_SUGGEST = 50.0; // 50-89: Suggest
    // <50: Manual reconciliation

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'bank_transaction_id',
        'invoice_id',
        'payment_id',
        'status',
        'match_type',
        'confidence',
        'match_details',
        'matched_by',
        'matched_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'match_details' => 'array',
            'matched_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */

    /**
     * Get the company that owns the reconciliation.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the bank transaction associated with this reconciliation.
     */
    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    /**
     * Get the invoice associated with this reconciliation.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment associated with this reconciliation.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who performed the match.
     */
    public function matchedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    /**
     * Alias for matchedByUser() for backward compatibility.
     */
    public function reconciledBy(): BelongsTo
    {
        return $this->matchedByUser();
    }

    /**
     * Get the feedback records for this reconciliation.
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(ReconciliationFeedback::class);
    }

    /**
     * Get the split allocations for this reconciliation.
     *
     * P0-14: Partial Payments + Multi-Invoice Settlement.
     */
    public function splits(): HasMany
    {
        return $this->hasMany(ReconciliationSplit::class);
    }

    /**
     * Scopes
     */

    /**
     * Scope: Get reconciliations for a specific company.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Get reconciliations for a specific company (alias).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Get pending reconciliations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Get pending reconciliations (alias).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Get reconciliations by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get reconciliations by status (alias).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get matched reconciliations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMatched($query)
    {
        return $query->where('status', self::STATUS_MATCHED);
    }

    /**
     * Scope: Get auto-matched reconciliations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAutoMatched($query)
    {
        return $query->where('status', self::STATUS_MATCHED)
            ->where('match_type', self::MATCH_TYPE_AUTO)
            ->where('confidence', '>=', self::THRESHOLD_AUTO_MATCH);
    }

    /**
     * Scope: Get suggested reconciliations (need approval).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuggested($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('confidence', '>=', self::THRESHOLD_SUGGEST)
            ->where('confidence', '<', self::THRESHOLD_AUTO_MATCH);
    }

    /**
     * Scope: Get reconciliations that need manual review.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManual($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('confidence')
                    ->orWhere('confidence', '<', self::THRESHOLD_SUGGEST);
            });
    }

    /**
     * Scope: Get reconciliations by match type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $matchType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMatchType($query, string $matchType)
    {
        return $query->where('match_type', $matchType);
    }

    /**
     * Scope: Get high confidence matches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighConfidence($query, float $threshold = 80.0)
    {
        return $query->where('confidence', '>=', $threshold);
    }

    /**
     * Helper Methods
     */

    /**
     * Check if this reconciliation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if this reconciliation is matched.
     */
    public function isMatched(): bool
    {
        return $this->status === self::STATUS_MATCHED;
    }

    /**
     * Check if this reconciliation was done manually.
     */
    public function isManual(): bool
    {
        return $this->match_type === self::MATCH_TYPE_MANUAL;
    }

    /**
     * Check if this reconciliation was done automatically.
     */
    public function isAutomatic(): bool
    {
        return $this->match_type === self::MATCH_TYPE_AUTO;
    }

    /**
     * Check if this reconciliation is auto-matched.
     */
    public function isAutoMatched(): bool
    {
        return $this->status === self::STATUS_MATCHED &&
               $this->match_type === self::MATCH_TYPE_AUTO &&
               $this->confidence >= self::THRESHOLD_AUTO_MATCH;
    }

    /**
     * Check if this reconciliation is a suggested match.
     */
    public function isSuggested(): bool
    {
        return $this->status === self::STATUS_PENDING &&
               $this->confidence >= self::THRESHOLD_SUGGEST &&
               $this->confidence < self::THRESHOLD_AUTO_MATCH;
    }

    /**
     * Check if this reconciliation needs manual review.
     */
    public function needsManualReview(): bool
    {
        return $this->status === self::STATUS_PENDING &&
               ($this->confidence === null || $this->confidence < self::THRESHOLD_SUGGEST);
    }

    /**
     * Mark as matched with invoice and optionally payment.
     *
     * @param  int|null  $invoiceId
     * @param  int|null  $paymentId
     * @param  string  $matchType
     * @param  float|null  $confidence
     * @param  int|null  $userId
     * @param  array|null  $details
     */
    public function markAsMatched(
        ?int $invoiceId = null,
        ?int $paymentId = null,
        string $matchType = self::MATCH_TYPE_MANUAL,
        ?float $confidence = null,
        ?int $userId = null,
        ?array $details = null
    ): bool {
        return $this->update([
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'status' => self::STATUS_MATCHED,
            'match_type' => $matchType,
            'confidence' => $confidence,
            'match_details' => $details,
            'matched_by' => $userId ?? auth()->id(),
            'matched_at' => now(),
        ]);
    }

    /**
     * Approve this reconciliation match (alias for markAsMatched).
     *
     * @param  int|null  $userId  User who approved
     * @param  string|null  $notes  Optional approval notes
     */
    public function approve(?int $userId = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_MATCHED,
            'match_type' => self::MATCH_TYPE_MANUAL,
            'matched_by' => $userId ?? auth()->id(),
            'matched_at' => now(),
            'match_details' => $notes ? array_merge($this->match_details ?? [], ['notes' => $notes]) : $this->match_details,
        ]);
    }

    /**
     * Mark the reconciliation as ignored.
     *
     * @param  int|null  $userId
     * @param  string|null  $reason
     */
    public function markAsIgnored(?int $userId = null, ?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_IGNORED,
            'match_type' => self::MATCH_TYPE_MANUAL,
            'matched_by' => $userId ?? auth()->id(),
            'matched_at' => now(),
            'match_details' => $reason ? ['ignore_reason' => $reason] : null,
        ]);
    }

    /**
     * Reject this reconciliation match (alias for markAsIgnored).
     *
     * @param  string  $reason  Rejection reason
     * @param  int|null  $userId  User who rejected
     */
    public function reject(string $reason, ?int $userId = null): bool
    {
        return $this->markAsIgnored($userId, $reason);
    }

    /**
     * Reset the reconciliation to pending status.
     */
    public function resetToPending(): bool
    {
        return $this->update([
            'invoice_id' => null,
            'payment_id' => null,
            'status' => self::STATUS_PENDING,
            'match_type' => self::MATCH_TYPE_AUTO,
            'confidence' => null,
            'match_details' => null,
            'matched_by' => null,
            'matched_at' => null,
        ]);
    }

    /**
     * Get confidence percentage (0-100).
     * Since confidence is already stored as 0-100, just return it.
     */
    public function getConfidencePercentageAttribute(): float
    {
        return round($this->confidence ?? 0, 2);
    }

    /**
     * Get confidence category.
     */
    public function getConfidenceCategoryAttribute(): string
    {
        if ($this->confidence === null) {
            return 'manual';
        }

        if ($this->confidence >= self::THRESHOLD_AUTO_MATCH) {
            return 'auto';
        } elseif ($this->confidence >= self::THRESHOLD_SUGGEST) {
            return 'suggested';
        } else {
            return 'manual';
        }
    }
}

// CLAUDE-CHECKPOINT
