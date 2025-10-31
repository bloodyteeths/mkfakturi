<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Silber\Bouncer\BouncerFacade;

class ItemPolicy
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
        
        if ($user->can('view-item', Item::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Item $item): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('view-item', $item) && $user->hasCompany($item->company_id)) {
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
        
        if ($user->can('create-item', Item::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Item $item): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('edit-item', $item) && $user->hasCompany($item->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Item $item): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('delete-item', $item) && $user->hasCompany($item->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, Item $item): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('delete-item', $item) && $user->hasCompany($item->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Item $item): bool
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('delete-item', $item) && $user->hasCompany($item->company_id)) {
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
        
        if ($user->can('delete-item', Item::class)) {
            return true;
        }

        return false;
    }
}
