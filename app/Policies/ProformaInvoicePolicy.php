<?php

namespace App\Policies;

use App\Models\ProformaInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Proforma Invoice Policy
 *
 * Defines authorization rules for proforma invoice operations
 */
class ProformaInvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any proforma invoices.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-proforma-invoice', ProformaInvoice::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the proforma invoice.
     */
    public function view(User $user, ProformaInvoice $proformaInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-proforma-invoice', $proformaInvoice) && $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create proforma invoices.
     */
    public function create(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('create-proforma-invoice', ProformaInvoice::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the proforma invoice.
     */
    public function update(User $user, ProformaInvoice $proformaInvoice): bool
    {
        // Cannot edit converted proforma invoices
        if (! $proformaInvoice->allow_edit) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('edit-proforma-invoice', $proformaInvoice) && $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the proforma invoice.
     */
    public function delete(User $user, ProformaInvoice $proformaInvoice): bool
    {
        // Cannot delete converted proforma invoices
        if ($proformaInvoice->status === ProformaInvoice::STATUS_CONVERTED) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-proforma-invoice', $proformaInvoice) && $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the proforma invoice.
     */
    public function restore(User $user, ProformaInvoice $proformaInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-proforma-invoice', $proformaInvoice) && $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the proforma invoice.
     */
    public function forceDelete(User $user, ProformaInvoice $proformaInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-proforma-invoice', $proformaInvoice) && $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can send the proforma invoice.
     */
    public function send(User $user, ProformaInvoice $proformaInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('send-proforma-invoice', $proformaInvoice) && $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark proforma invoice as viewed.
     */
    public function markAsViewed(User $user, ProformaInvoice $proformaInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('edit-proforma-invoice', $proformaInvoice) && $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark proforma invoice as expired.
     */
    public function markAsExpired(User $user, ProformaInvoice $proformaInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('edit-proforma-invoice', $proformaInvoice) && $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark proforma invoice as rejected.
     */
    public function markAsRejected(User $user, ProformaInvoice $proformaInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('edit-proforma-invoice', $proformaInvoice) && $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can convert proforma invoice to invoice.
     */
    public function convertToInvoice(User $user, ProformaInvoice $proformaInvoice): bool
    {
        // Can only convert non-expired, non-rejected, non-converted proforma invoices
        if (in_array($proformaInvoice->status, [
            ProformaInvoice::STATUS_EXPIRED,
            ProformaInvoice::STATUS_REJECTED,
            ProformaInvoice::STATUS_CONVERTED,
        ])) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('create-invoice', \App\Models\Invoice::class) &&
            $user->can('edit-proforma-invoice', $proformaInvoice) &&
            $user->hasCompany($proformaInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete multiple proforma invoices.
     */
    public function deleteMultiple(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-proforma-invoice', ProformaInvoice::class)) {
            return true;
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
