<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * ApprovalPolicy
 *
 * Manages permissions for document approval requests.
 * Requires specific abilities for requesting and approving documents.
 */
class ApprovalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any approval requests.
     */
    public function viewAny(User $user): bool
    {
        // Owners can view all approval requests
        if ($user->isOwner()) {
            return true;
        }

        // Users with view-approval or approve-document ability can view
        if ($user->can('view-approval', ApprovalRequest::class) ||
            $user->can('approve-document', ApprovalRequest::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the approval request.
     */
    public function view(User $user, ApprovalRequest $approval): bool
    {
        // Owners can view all
        if ($user->isOwner()) {
            return true;
        }

        // Users can view their own requests
        if ($approval->requested_by === $user->id) {
            return true;
        }

        // Users with view-approval or approve-document ability can view
        if ($user->can('view-approval', $approval) && $user->hasCompany($approval->company_id)) {
            return true;
        }

        if ($user->can('approve-document', $approval) && $user->hasCompany($approval->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can request approval for a document.
     * Requires create permission for the document type.
     */
    public function requestApproval(User $user): bool
    {
        // Owners can request approvals
        if ($user->isOwner()) {
            return true;
        }

        // Users who can create documents can request approval
        if ($user->can('create-invoice') ||
            $user->can('create-estimate') ||
            $user->can('create-expense') ||
            $user->can('create-bill') ||
            $user->can('create-credit-note')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve a document.
     * Requires approve-document ability.
     */
    public function approve(User $user, ?ApprovalRequest $approval = null): bool
    {
        // Owners can approve all
        if ($user->isOwner()) {
            return true;
        }

        // Users cannot approve their own requests
        if ($approval && $approval->requested_by === $user->id) {
            return false;
        }

        // Check approve-document ability
        if ($approval) {
            if ($user->can('approve-document', $approval) && $user->hasCompany($approval->company_id)) {
                return true;
            }
        } else {
            if ($user->can('approve-document', ApprovalRequest::class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can reject a document.
     * Requires approve-document ability (same as approve).
     */
    public function reject(User $user, ?ApprovalRequest $approval = null): bool
    {
        // Same permissions as approve
        return $this->approve($user, $approval);
    }

    /**
     * Determine whether the user can delete the approval request.
     * Only the requester or owner can delete pending requests.
     */
    public function delete(User $user, ApprovalRequest $approval): bool
    {
        // Owners can delete
        if ($user->isOwner()) {
            return true;
        }

        // Requester can delete their own pending requests
        if ($approval->requested_by === $user->id && $approval->isPending()) {
            return true;
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
