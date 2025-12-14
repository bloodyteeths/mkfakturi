<?php

namespace App\Policies;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EstimatePolicy
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

        if ($user->can('view-estimate', Estimate::class) || $user->role === 'partner') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Estimate $estimate): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerAccessToCompany($estimate->company_id);
        }

        if ($user->can('view-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
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

        if ($user->can('create-estimate', Estimate::class) || $user->role === 'partner') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Estimate $estimate): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerAccessToCompany($estimate->company_id);
        }

        if ($user->can('edit-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Estimate $estimate): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerAccessToCompany($estimate->company_id);
        }

        if ($user->can('delete-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, Estimate $estimate): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Estimate $estimate): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can send email of the model.
     *
     * @param  \App\Models\Estimate  $payment
     * @return mixed
     */
    public function send(User $user, Estimate $estimate)
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerAccessToCompany($estimate->company_id);
        }

        if ($user->can('send-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
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

        if ($user->can('delete-estimate', Estimate::class) || $user->role === 'partner') {
            return true;
        }

        return false;
    }
}
