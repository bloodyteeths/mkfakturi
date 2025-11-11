<?php

namespace App\Models;

use App\Traits\HasAuditing;
use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EInvoiceSubmission Model
 *
 * Tracks submission attempts of e-invoices to the tax authority.
 * Stores submission status, response data, and retry information.
 *
 * @property int $id
 * @property int $e_invoice_id
 * @property int|null $submitted_by User ID who submitted
 * @property \Carbon\Carbon $submitted_at
 * @property string|null $portal_url
 * @property string|null $receipt_number
 * @property string $status
 * @property array|null $response_data
 * @property int $retry_count
 * @property \Carbon\Carbon|null $next_retry_at
 * @property string|null $error_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read EInvoice $eInvoice
 * @property-read User|null $submittedBy
 */
class EInvoiceSubmission extends Model
{
    use HasAuditing;
    use HasFactory;
    use TenantScope;

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_ERROR = 'ERROR';

    /**
     * Maximum retry attempts
     */
    public const MAX_RETRIES = 3;

    /**
     * Retry delay in minutes
     */
    public const RETRY_DELAY_MINUTES = 30;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'e_invoice_submissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'e_invoice_id',
        'company_id',
        'submitted_by',
        'submitted_at',
        'portal_url',
        'receipt_number',
        'status',
        'response_data',
        'retry_count',
        'next_retry_at',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_data' => 'array',
            'submitted_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    /**
     * Default eager loaded relationships
     */
    protected $with = [
        'eInvoice:id,invoice_id,status',
        'submittedBy:id,name,email',
    ];

    /**
     * Get the e-invoice that this submission belongs to.
     *
     * @return BelongsTo
     */
    public function eInvoice(): BelongsTo
    {
        return $this->belongsTo(EInvoice::class);
    }

    /**
     * Get the user who submitted this e-invoice.
     *
     * @return BelongsTo
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the company (via e-invoice relationship).
     * This scope enables the TenantScope trait to work correctly.
     *
     * @return int|null
     */
    public function getCompanyIdAttribute(): ?int
    {
        return $this->eInvoice?->company_id ?? $this->attributes['company_id'] ?? null;
    }

    /**
     * Retry the submission.
     * Increments retry count and schedules next retry.
     *
     * @return bool
     */
    public function retry(): bool
    {
        if ($this->retry_count >= self::MAX_RETRIES) {
            return false;
        }

        $this->retry_count++;
        $this->next_retry_at = now()->addMinutes(self::RETRY_DELAY_MINUTES * $this->retry_count);
        $this->status = self::STATUS_PENDING;

        return $this->save();
    }

    /**
     * Mark the submission as accepted.
     *
     * @param string|null $receiptNumber
     * @param array|null $responseData
     * @return bool
     */
    public function markAsAccepted(?string $receiptNumber = null, ?array $responseData = null): bool
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->receipt_number = $receiptNumber ?? $this->receipt_number;

        if ($responseData) {
            $this->response_data = array_merge($this->response_data ?? [], $responseData);
        }

        // Clear retry schedule
        $this->next_retry_at = null;
        $this->error_message = null;

        // Also update the parent e-invoice
        $this->eInvoice?->markAsAccepted();

        return $this->save();
    }

    /**
     * Mark the submission as rejected.
     *
     * @param string|null $reason
     * @param array|null $responseData
     * @return bool
     */
    public function markAsRejected(?string $reason = null, ?array $responseData = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->error_message = $reason;

        if ($responseData) {
            $this->response_data = array_merge($this->response_data ?? [], $responseData);
        }

        // Clear retry schedule
        $this->next_retry_at = null;

        // Also update the parent e-invoice
        $this->eInvoice?->markAsRejected($reason);

        return $this->save();
    }

    /**
     * Mark the submission as error.
     *
     * @param string $errorMessage
     * @param array|null $responseData
     * @param bool $scheduleRetry
     * @return bool
     */
    public function markAsError(string $errorMessage, ?array $responseData = null, bool $scheduleRetry = true): bool
    {
        $this->status = self::STATUS_ERROR;
        $this->error_message = $errorMessage;

        if ($responseData) {
            $this->response_data = array_merge($this->response_data ?? [], $responseData);
        }

        // Schedule retry if enabled and under max retries
        if ($scheduleRetry && $this->retry_count < self::MAX_RETRIES) {
            $this->retry();
        } else {
            $this->next_retry_at = null;
            // Mark parent e-invoice as failed if max retries exceeded
            if ($this->retry_count >= self::MAX_RETRIES) {
                $this->eInvoice?->markAsFailed($errorMessage);
            }
        }

        return $this->save();
    }

    /**
     * Check if the submission can be retried.
     *
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->retry_count < self::MAX_RETRIES
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_ERROR]);
    }

    /**
     * Check if the submission is pending retry.
     *
     * @return bool
     */
    public function isPendingRetry(): bool
    {
        return $this->next_retry_at && $this->next_retry_at->isFuture();
    }

    /**
     * Check if the submission is ready for retry.
     *
     * @return bool
     */
    public function isReadyForRetry(): bool
    {
        return $this->canRetry()
            && $this->next_retry_at
            && $this->next_retry_at->isPast();
    }

    /**
     * Scope: filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: get pending submissions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: get accepted submissions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope: get rejected submissions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope: get error submissions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeError($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }

    /**
     * Scope: get submissions ready for retry.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReadyForRetry($query)
    {
        return $query->where('retry_count', '<', self::MAX_RETRIES)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_ERROR])
            ->where('next_retry_at', '<=', now());
    }

    /**
     * Scope: filter by company.
     * Now uses direct company_id column instead of relationship join.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        if ($companyId) {
            return $query->where('company_id', $companyId);
        }

        return $query;
    }
}

// CLAUDE-CHECKPOINT
