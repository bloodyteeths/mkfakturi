<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
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
        if ($user->can('view-supplier', Supplier::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return bool
     */
    public function view(User $user, Supplier $supplier): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
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

        if ($user->can('create-supplier', Supplier::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return bool
     */
    public function update(User $user, Supplier $supplier): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('edit-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return bool
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return bool
     */
    public function restore(User $user, Supplier $supplier): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return bool
     */
    public function forceDelete(User $user, Supplier $supplier): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
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

        if ($user->can('delete-supplier', Supplier::class)) {
            return true;
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
