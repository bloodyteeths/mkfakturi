<?php

namespace App\Policies;

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
        \Log::info('InvoicePolicy::viewAny called', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'is_owner' => $user->isOwner(),
        ]);

        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            \Log::info('InvoicePolicy::viewAny - Owner access granted', [
                'user_id' => $user->id,
            ]);
            return true;
        }

        // Allow partners explicitly
        if ($user->role === 'partner') {
            \Log::info('InvoicePolicy::viewAny - Partner access granted', [
                'user_id' => $user->id,
            ]);
            return true;
        }

        // Use the user instance to check abilities (respects Bouncer scope)
        $canViewInvoice = $user->can('view-invoice', Invoice::class);

        \Log::info('InvoicePolicy::viewAny - Final decision', [
            'user_id' => $user->id,
            'can_view_invoice' => $canViewInvoice,
        ]);

        if ($canViewInvoice) {
            return true;
        }

        \Log::warning('InvoicePolicy::viewAny - Access DENIED', [
            'user_id' => $user->id,
            'user_role' => $user->role,
        ]);

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Invoice $invoice)
    {
        \Log::info('InvoicePolicy::view called', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'invoice_id' => $invoice->id,
            'invoice_company_id' => $invoice->company_id,
            'user_has_company' => $user->hasCompany($invoice->company_id),
            'is_partner' => $user->role === 'partner',
        ]);

        // Allow partners explicitly (temporary fallback)
        if ($user->role === 'partner') {
            \Log::info('InvoicePolicy::view - Partner access granted', [
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
            ]);
            return true;
        }

        $hasAccess = $user->hasCompany($invoice->company_id);

        \Log::info('InvoicePolicy::view - Final decision', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'has_access' => $hasAccess,
        ]);

        return $hasAccess;
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

        if ($user->can('edit-invoice', $invoice) && ($user->hasCompany($invoice->company_id) || $user->role === 'partner')) {
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

        if ($user->can('delete-invoice', $invoice) && ($user->hasCompany($invoice->company_id) || $user->role === 'partner')) {
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

        if ($user->can('delete-invoice', $invoice) && ($user->hasCompany($invoice->company_id) || $user->role === 'partner')) {
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

        if ($user->can('delete-invoice', $invoice) && ($user->hasCompany($invoice->company_id) || $user->role === 'partner')) {
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

        if ($user->can('send-invoice', $invoice) && ($user->hasCompany($invoice->company_id) || $user->role === 'partner')) {
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

        if ($user->can('delete-invoice', Invoice::class)) {
            return true;
        }

        return false;
    }
}
