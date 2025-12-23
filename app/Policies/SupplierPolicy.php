<?php

namespace App\Policies;

use App\Enums\PartnerPermission;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            $companyId = (int) request()->header('company');
            return $user->hasPartnerPermission($companyId, PartnerPermission::VIEW_SUPPLIERS);
        }

        if ($user->can('view-supplier', Supplier::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Supplier $supplier): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($supplier->company_id, PartnerPermission::VIEW_SUPPLIERS);
        }

        if ($user->can('view-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
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

        if ($user->role === 'partner') {
            $companyId = (int) request()->header('company');
            return $user->hasPartnerPermission($companyId, PartnerPermission::CREATE_SUPPLIERS);
        }

        if ($user->can('create-supplier', Supplier::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($supplier->company_id, PartnerPermission::EDIT_SUPPLIERS);
        }

        if ($user->can('edit-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($supplier->company_id, PartnerPermission::DELETE_SUPPLIERS);
        }

        if ($user->can('delete-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Supplier $supplier): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($supplier->company_id, PartnerPermission::DELETE_SUPPLIERS);
        }

        if ($user->can('delete-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Supplier $supplier): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($supplier->company_id, PartnerPermission::DELETE_SUPPLIERS);
        }

        if ($user->can('delete-supplier', $supplier) && $user->hasCompany($supplier->company_id)) {
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

        if ($user->role === 'partner') {
            $companyId = (int) request()->header('company');
            return $user->hasPartnerPermission($companyId, PartnerPermission::DELETE_SUPPLIERS);
        }

        if ($user->can('delete-supplier', Supplier::class)) {
            return true;
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
