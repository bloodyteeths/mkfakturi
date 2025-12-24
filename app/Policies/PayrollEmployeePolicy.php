<?php

namespace App\Policies;

use App\Models\PayrollEmployee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollEmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super admin can access all
        if ($user->role === 'super admin') {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-payroll-employee', PayrollEmployee::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PayrollEmployee $payrollEmployee): bool
    {
        if ($user->role === 'super admin') {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-payroll-employee', PayrollEmployee::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->role === 'super admin') {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('create-payroll-employee', PayrollEmployee::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PayrollEmployee $payrollEmployee): bool
    {
        if ($user->role === 'super admin') {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('edit-payroll-employee', PayrollEmployee::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PayrollEmployee $payrollEmployee): bool
    {
        if ($user->role === 'super admin') {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-payroll-employee', PayrollEmployee::class)) {
            return true;
        }

        return false;
    }
}

// LLM-CHECKPOINT
