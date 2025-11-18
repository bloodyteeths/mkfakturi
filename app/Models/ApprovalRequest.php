<?php

namespace App\Models;

use App\Traits\HasAuditing;
use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ApprovalRequest Model
 *
 * Manages document approval workflow for Invoice, Estimate, Expense, Bill, CreditNote.
 * Blocks document sending/signing until approved.
 *
 * IMPORTANT: This is a stub implementation ready for ringlesoft/laravel-process-approval package.
 * When the package is installed, this model can be extended or replaced.
 */
class ApprovalRequest extends Model
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

    /**
     * Approvable types
     */
    const TYPE_INVOICE = 'App\\Models\\Invoice';

    const TYPE_ESTIMATE = 'App\\Models\\Estimate';

    const TYPE_EXPENSE = 'App\\Models\\Expense';

    const TYPE_BILL = 'App\\Models\\Bill';

    const TYPE_CREDIT_NOTE = 'App\\Models\\CreditNote';

    protected $fillable = [
        'company_id',
        'approvable_type',
        'approvable_id',
        'status',
        'requested_by',
        'approved_by',
        'approval_note',
        'request_note',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    protected $dates = [
        'approved_at',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the approvable document (Invoice, Estimate, etc.)
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeForDocument($query, string $type, int $id)
    {
        return $query->where('approvable_type', $type)
            ->where('approvable_id', $id);
    }

    /**
     * Helper Methods
     */

    /**
     * Check if approval is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if approval was approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if approval was rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Approve this request
     *
     * @param  int|null  $userId  User who approved
     * @param  string|null  $note  Optional approval note
     */
    public function approve(?int $userId = null, ?string $note = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
            'approval_note' => $note,
        ]);
    }

    /**
     * Reject this request
     *
     * @param  string  $note  Rejection reason
     * @param  int|null  $userId  User who rejected
     */
    public function reject(string $note, ?int $userId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
            'approval_note' => $note,
        ]);
    }

    /**
     * Get human-readable document type
     */
    public function getDocumentTypeNameAttribute(): string
    {
        return match ($this->approvable_type) {
            self::TYPE_INVOICE => 'Invoice',
            self::TYPE_ESTIMATE => 'Estimate',
            self::TYPE_EXPENSE => 'Expense',
            self::TYPE_BILL => 'Bill',
            self::TYPE_CREDIT_NOTE => 'Credit Note',
            default => 'Document',
        };
    }

    /**
     * Get document identifier (invoice number, etc.)
     */
    public function getDocumentIdentifierAttribute(): ?string
    {
        if (! $this->approvable) {
            return null;
        }

        return match ($this->approvable_type) {
            self::TYPE_INVOICE => $this->approvable->invoice_number,
            self::TYPE_ESTIMATE => $this->approvable->estimate_number,
            self::TYPE_EXPENSE => 'EXP-'.$this->approvable->id,
            self::TYPE_BILL => $this->approvable->bill_number,
            self::TYPE_CREDIT_NOTE => $this->approvable->credit_note_number,
            default => '#'.$this->approvable->id,
        };
    }
}

// CLAUDE-CHECKPOINT
