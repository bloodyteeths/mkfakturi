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
        \Log::info('[EInvoicePolicy::viewAny] Authorization check', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'is_owner' => $user->isOwner(),
            'company_id' => auth()->user()->company->id ?? 'N/A',
        ]);

        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            \Log::info('[EInvoicePolicy::viewAny] User is owner - AUTHORIZED');
            return true;
        }

        // Use the user instance to check abilities (respects Bouncer scope)
        $canView = $user->can('view-einvoice', EInvoice::class);
        \Log::info('[EInvoicePolicy::viewAny] Ability check result', [
            'can_view_einvoice' => $canView,
            'abilities' => $user->getAbilities()->pluck('name')->toArray(),
        ]);

        if ($canView) {
            \Log::info('[EInvoicePolicy::viewAny] User has view-einvoice ability - AUTHORIZED');
            return true;
        }

        \Log::warning('[EInvoicePolicy::viewAny] User DENIED - no permissions');
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
        \Log::info('[EInvoicePolicy::create] Authorization check for generate', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'is_owner' => $user->isOwner(),
            'company_id' => auth()->user()->company->id ?? 'N/A',
        ]);

        if ($user->isOwner()) {
            \Log::info('[EInvoicePolicy::create] User is owner - AUTHORIZED');
            return true;
        }

        $canGenerate = $user->can('generate-einvoice', EInvoice::class);
        \Log::info('[EInvoicePolicy::create] Ability check result', [
            'can_generate_einvoice' => $canGenerate,
            'all_abilities' => $user->getAbilities()->pluck('name')->toArray(),
        ]);

        if ($canGenerate) {
            \Log::info('[EInvoicePolicy::create] User has generate-einvoice ability - AUTHORIZED');
            return true;
        }

        \Log::warning('[EInvoicePolicy::create] User DENIED - no generate permissions');
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
