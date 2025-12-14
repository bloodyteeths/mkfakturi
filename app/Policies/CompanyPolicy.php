<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Company $company): bool
    {
        // Allow viewing if user owns the company
        if ($user->id == $company->owner_id) {
            return true;
        }

        // Allow viewing if user is part of the company (for multi-company setups)
        if ($user->companies && $user->companies->contains($company)) {
            return true;
        }

        // For now, allow all authenticated admin users to view company data
        // This can be refined later with proper role-based permissions
        if ($user->isOwner()) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Company $company): bool
    {
        if ($user->id == $company->owner_id) {
            return true;
        }

        return false;
    }

    public function transferOwnership(User $user, Company $company)
    {
        if ($user->id == $company->owner_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can manage billing for the company.
     * Only company owners and super admins can manage billing.
     */
    public function manageBilling(User $user, Company $company): bool
    {
        // Super admin can always manage billing
        if ($user->role === 'super admin' || $user->is_super_admin) {
            return true;
        }

        // Company owner can manage billing
        if ($user->id == $company->owner_id) {
            return true;
        }

        // Check if user is an owner-level user in this company
        if ($user->isOwner()) {
            return true;
        }

        return false;
    }
}
