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
     *
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Use the user instance to check abilities (respects Bouncer scope)
        if ($user->can('view-bill', Bill::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return bool
     */
    public function view(User $user, Bill $bill): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-bill', $bill) && $user->hasCompany($bill->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return bool
     */
    public function create(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('create-bill', Bill::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return bool
     */
    public function update(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return $bill->allow_edit;
        }

        if ($user->can('edit-bill', $bill) && $user->hasCompany($bill->company_id)) {
            return $bill->allow_edit;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return bool
     */
    public function delete(User $user, Bill $bill): bool
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
     * Determine whether the user can restore the model.
     *
     * @return bool
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
     *
     * @return bool
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
     *
     * @return bool
     */
    public function send(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('send-bill', $bill) && $user->hasCompany($bill->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark bill as viewed.
     *
     * @return bool
     */
    public function markAsViewed(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('edit-bill', $bill) && $user->hasCompany($bill->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark bill as completed.
     *
     * @return bool
     */
    public function markAsCompleted(User $user, Bill $bill): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('edit-bill', $bill) && $user->hasCompany($bill->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete models.
     *
     * @return bool
     */
    public function deleteMultiple(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-bill', Bill::class)) {
            return true;
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
