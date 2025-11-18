<?php

namespace App\Policies;

use App\Models\CreditNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Credit Note Policy
 *
 * Handles authorization for credit note operations.
 * Based on InvoicePolicy pattern with Bouncer abilities.
 */
class CreditNotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any credit notes.
     */
    public function viewAny(User $user): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Use the user instance to check abilities (respects Bouncer scope)
        if ($user->can('view-credit-note', CreditNote::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the credit note.
     */
    public function view(User $user, CreditNote $creditNote): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-credit-note', $creditNote) && $user->hasCompany($creditNote->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create credit notes.
     */
    public function create(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('create-credit-note', CreditNote::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the credit note.
     *
     * Respects allow_edit attribute which checks:
     * - If posted to IFRS (immutable)
     * - Retrospective edit settings
     */
    public function update(User $user, CreditNote $creditNote): bool
    {
        if ($user->isOwner()) {
            return $creditNote->allow_edit;
        }

        if ($user->can('edit-credit-note', $creditNote) && $user->hasCompany($creditNote->company_id)) {
            return $creditNote->allow_edit;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the credit note.
     *
     * Deletion is prevented for credit notes posted to IFRS
     * (handled in model's deleteCreditNotes method).
     */
    public function delete(User $user, CreditNote $creditNote): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-credit-note', $creditNote) && $user->hasCompany($creditNote->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the credit note.
     */
    public function restore(User $user, CreditNote $creditNote): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-credit-note', $creditNote) && $user->hasCompany($creditNote->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the credit note.
     */
    public function forceDelete(User $user, CreditNote $creditNote): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-credit-note', $creditNote) && $user->hasCompany($creditNote->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can send email of the credit note.
     */
    public function send(User $user, CreditNote $creditNote): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('send-credit-note', $creditNote) && $user->hasCompany($creditNote->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete multiple credit notes.
     */
    public function deleteMultiple(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-credit-note', CreditNote::class)) {
            return true;
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
