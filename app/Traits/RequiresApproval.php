<?php

namespace App\Traits;

use App\Models\ApprovalRequest;
use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * RequiresApproval Trait
 *
 * Adds document approval workflow functionality to models.
 * Blocks document sending/signing until approved when enabled.
 *
 * Usage: Add to Invoice, Estimate, Expense, Bill, CreditNote models:
 *   use RequiresApproval;
 */
trait RequiresApproval
{
    /**
     * Get all approval requests for this document
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest approval request
     */
    public function latestApprovalRequest(): ?ApprovalRequest
    {
        return $this->approvalRequests()->first();
    }

    /**
     * Check if this document type requires approval based on company settings
     */
    public function requiresApproval(): bool
    {
        $companyId = $this->company_id ?? request()->header('company');

        if (! $companyId) {
            return false;
        }

        // Check company-level setting for approvals
        $approvalsEnabled = CompanySetting::getSetting('enable_document_approvals', $companyId);

        if ($approvalsEnabled !== 'YES') {
            return false;
        }

        // Check document-type specific setting
        $documentType = class_basename($this);
        $settingKey = 'require_approval_'.strtolower($documentType);
        $requireApproval = CompanySetting::getSetting($settingKey, $companyId);

        return $requireApproval === 'YES';
    }

    /**
     * Request approval for this document
     *
     * @param  string|null  $note  Optional note from requester
     */
    public function requestApproval(?string $note = null): ApprovalRequest
    {
        // Check if there's already a pending approval
        $existingPending = $this->approvalRequests()
            ->wherePending()
            ->first();

        if ($existingPending) {
            return $existingPending;
        }

        // Create new approval request
        return ApprovalRequest::create([
            'company_id' => $this->company_id,
            'approvable_type' => get_class($this),
            'approvable_id' => $this->id,
            'status' => ApprovalRequest::STATUS_PENDING,
            'requested_by' => auth()->id(),
            'request_note' => $note,
        ]);
    }

    /**
     * Check if this document has been approved
     */
    public function isApproved(): bool
    {
        // If approvals not required, consider it approved
        if (! $this->requiresApproval()) {
            return true;
        }

        // Check for approved approval request
        return $this->approvalRequests()
            ->where('status', ApprovalRequest::STATUS_APPROVED)
            ->exists();
    }

    /**
     * Check if this document has a pending approval request
     */
    public function hasPendingApproval(): bool
    {
        return $this->approvalRequests()
            ->wherePending()
            ->exists();
    }

    /**
     * Check if this document was rejected
     */
    public function wasRejected(): bool
    {
        $latest = $this->latestApprovalRequest();

        return $latest && $latest->isRejected();
    }

    /**
     * Check if this document can be sent/signed
     * Blocks sending until approved if approvals are required
     */
    public function canBeSent(): bool
    {
        // If approvals not required, can always be sent
        if (! $this->requiresApproval()) {
            return true;
        }

        // Must be approved to be sent
        return $this->isApproved();
    }

    /**
     * Get approval status string
     */
    public function getApprovalStatusAttribute(): string
    {
        if (! $this->requiresApproval()) {
            return 'not_required';
        }

        $latest = $this->latestApprovalRequest();

        if (! $latest) {
            return 'not_requested';
        }

        return $latest->status;
    }

    /**
     * Get approval status color for UI
     */
    public function getApprovalStatusColorAttribute(): string
    {
        return match ($this->approval_status) {
            'not_required' => 'gray',
            'not_requested' => 'yellow',
            'pending' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Scope: Get documents requiring approval
     */
    public function scopeRequiringApproval($query)
    {
        return $query->whereHas('approvalRequests', function ($q) {
            $q->wherePending();
        });
    }

    /**
     * Scope: Get approved documents
     */
    public function scopeApproved($query)
    {
        return $query->whereHas('approvalRequests', function ($q) {
            $q->where('status', ApprovalRequest::STATUS_APPROVED);
        });
    }

    /**
     * Scope: Get documents without approval (not required or already approved)
     */
    public function scopeWithoutPendingApproval($query)
    {
        return $query->whereDoesntHave('approvalRequests', function ($q) {
            $q->wherePending();
        });
    }
}

// CLAUDE-CHECKPOINT
