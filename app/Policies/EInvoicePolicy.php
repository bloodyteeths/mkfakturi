<?php

namespace App\Policies;

use App\Models\EInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * EInvoicePolicy
 *
 * Authorization policy for electronic invoice operations.
 * Controls access to view, generate, sign, submit, and manage e-invoices.
 */
class EInvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any e-invoices.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Use the user instance to check abilities (respects Bouncer scope)
        if ($user->can('view-einvoice', EInvoice::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the e-invoice.
     *
     * @param User $user
     * @param EInvoice $eInvoice
     * @return bool
     */
    public function view(User $user, EInvoice $eInvoice): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-einvoice', $eInvoice) && $user->hasCompany($eInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can generate/create e-invoices.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('generate-einvoice', EInvoice::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update/sign the e-invoice.
     *
     * @param User $user
     * @param EInvoice $eInvoice
     * @return bool
     */
    public function update(User $user, EInvoice $eInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Only allow updating if e-invoice is in DRAFT or FAILED status
        if (!in_array($eInvoice->status, [EInvoice::STATUS_DRAFT, EInvoice::STATUS_FAILED])) {
            return false;
        }

        if ($user->can('generate-einvoice', $eInvoice) && $user->hasCompany($eInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the e-invoice.
     *
     * @param User $user
     * @param EInvoice $eInvoice
     * @return bool
     */
    public function delete(User $user, EInvoice $eInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Only allow deleting if e-invoice is in DRAFT or FAILED status
        if (!in_array($eInvoice->status, [EInvoice::STATUS_DRAFT, EInvoice::STATUS_FAILED])) {
            return false;
        }

        if ($user->can('generate-einvoice', $eInvoice) && $user->hasCompany($eInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can submit the e-invoice to tax authority.
     *
     * @param User $user
     * @param EInvoice $eInvoice
     * @return bool
     */
    public function submit(User $user, EInvoice $eInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Only allow submitting if e-invoice is SIGNED
        if ($eInvoice->status !== EInvoice::STATUS_SIGNED) {
            return false;
        }

        if ($user->can('submit-einvoice', $eInvoice) && $user->hasCompany($eInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can sign the e-invoice.
     *
     * @param User $user
     * @param EInvoice $eInvoice
     * @return bool
     */
    public function sign(User $user, EInvoice $eInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Only allow signing if e-invoice is in DRAFT status
        if ($eInvoice->status !== EInvoice::STATUS_DRAFT) {
            return false;
        }

        if ($user->can('generate-einvoice', $eInvoice) && $user->hasCompany($eInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can download the e-invoice XML.
     *
     * @param User $user
     * @param EInvoice $eInvoice
     * @return bool
     */
    public function download(User $user, EInvoice $eInvoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-einvoice', $eInvoice) && $user->hasCompany($eInvoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete multiple e-invoices.
     *
     * @param User $user
     * @return bool
     */
    public function deleteMultiple(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('generate-einvoice', EInvoice::class)) {
            return true;
        }

        return false;
    }
}
// CLAUDE-CHECKPOINT
