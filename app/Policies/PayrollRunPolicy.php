<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollRunPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->role === 'super admin') {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-payroll-employee', \App\Models\PayrollEmployee::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PayrollRun $payrollRun): bool
    {
        if ($user->role === 'super admin') {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-payroll-employee', \App\Models\PayrollEmployee::class)) {
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

        if ($user->can('create-payroll-employee', \App\Models\PayrollEmployee::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PayrollRun $payrollRun): bool
    {
        if ($user->role === 'super admin') {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('edit-payroll-employee', \App\Models\PayrollEmployee::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PayrollRun $payrollRun): bool
    {
        if ($user->role === 'super admin') {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('delete-payroll-employee', \App\Models\PayrollEmployee::class)) {
            return true;
        }

        return false;
    }
}

// LLM-CHECKPOINT
