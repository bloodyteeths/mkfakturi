<?php

namespace App\Policies;

use App\Enums\PartnerPermission;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Partners need VIEW_INVOICES permission
        if ($user->role === 'partner') {
            $companyId = (int) request()->header('company');
            return $user->hasPartnerPermission($companyId, PartnerPermission::VIEW_INVOICES);
        }

        return $user->can('view-invoice', Invoice::class);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Invoice $invoice)
    {
        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($invoice->company_id, PartnerPermission::VIEW_INVOICES);
        }

        return $user->hasCompany($invoice->company_id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Partners need CREATE_INVOICES permission
        if ($user->role === 'partner') {
            $companyId = (int) request()->header('company');
            return $user->hasPartnerPermission($companyId, PartnerPermission::CREATE_INVOICES);
        }

        if ($user->can('create-invoice', Invoice::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Invoice $invoice): bool
    {
        if ($user->isOwner()) {
            return $invoice->allow_edit;
        }

        // Partners need EDIT_INVOICES permission
        if ($user->role === 'partner') {
            if ($user->hasPartnerPermission($invoice->company_id, PartnerPermission::EDIT_INVOICES)) {
                return $invoice->allow_edit;
            }
            return false;
        }

        if ($user->can('edit-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
            return $invoice->allow_edit;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Partners need DELETE_INVOICES permission
        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($invoice->company_id, PartnerPermission::DELETE_INVOICES);
        }

        if ($user->can('delete-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Partners need DELETE_INVOICES permission (restore is related to delete)
        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($invoice->company_id, PartnerPermission::DELETE_INVOICES);
        }

        if ($user->can('delete-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Partners need DELETE_INVOICES permission
        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($invoice->company_id, PartnerPermission::DELETE_INVOICES);
        }

        if ($user->can('delete-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can send email of the model.
     *
     * @param  \App\Models\Payment  $payment
     * @return mixed
     */
    public function send(User $user, Invoice $invoice)
    {
        if ($user->isOwner()) {
            return true;
        }

        // Partners need SEND_INVOICES permission
        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($invoice->company_id, PartnerPermission::SEND_INVOICES);
        }

        if ($user->can('send-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete models.
     *
     * @return mixed
     */
    public function deleteMultiple(User $user)
    {
        if ($user->isOwner()) {
            return true;
        }

        // Partners need DELETE_INVOICES permission
        if ($user->role === 'partner') {
            $companyId = (int) request()->header('company');
            return $user->hasPartnerPermission($companyId, PartnerPermission::DELETE_INVOICES);
        }

        if ($user->can('delete-invoice', Invoice::class)) {
            return true;
        }

        return false;
    }
}
// CLAUDE-CHECKPOINT
