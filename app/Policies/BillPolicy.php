<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Use the user instance to check abilities (respects Bouncer scope)
        if ($user->can('view-bill', Bill::class) || $user->role === 'partner') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Bill $bill): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        if (($user->can('view-bill', $bill) && $user->hasCompany($bill->company_id)) || $user->role === 'partner') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('create-bill', Bill::class) || $user->role === 'partner') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return $bill->allow_edit;
        }

        if (($user->can('edit-bill', $bill) && $user->hasCompany($bill->company_id)) || $user->role === 'partner') {
            return $bill->allow_edit;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if (($user->can('delete-bill', $bill) && $user->hasCompany($bill->company_id)) || $user->role === 'partner') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-bill', $bill) && $user->hasCompany($bill->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-bill', $bill) && $user->hasCompany($bill->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can send the bill.
     */
    public function send(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if (($user->can('send-bill', $bill) && $user->hasCompany($bill->company_id)) || $user->role === 'partner') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark bill as viewed.
     */
    public function markAsViewed(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if (($user->can('edit-bill', $bill) && $user->hasCompany($bill->company_id)) || $user->role === 'partner') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark bill as completed.
     */
    public function markAsCompleted(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if (($user->can('edit-bill', $bill) && $user->hasCompany($bill->company_id)) || $user->role === 'partner') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete models.
     */
    public function deleteMultiple(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-bill', Bill::class) || $user->role === 'partner') {
            return true;
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
