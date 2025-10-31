<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Silber\Bouncer\BouncerFacade;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }
        
        // Use the user instance to check abilities (respects Bouncer scope)
        if ($user->can('view-customer', Customer::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Customer $customer): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('view-customer', $customer)) {
            return true;
        }

        return false;
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
        
        if ($user->can('create-customer', Customer::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Customer $customer): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('edit-customer', $customer)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Customer $customer): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('delete-customer', $customer)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, Customer $customer): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('delete-customer', $customer)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('delete-customer', $customer)) {
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
        
        if ($user->can('delete-customer', Customer::class)) {
            return true;
        }

        return false;
    }
}
