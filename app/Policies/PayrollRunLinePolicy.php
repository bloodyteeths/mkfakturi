<?php

namespace App\Policies;

use App\Models\PayrollRunLine;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollRunLinePolicy
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
    public function view(User $user, PayrollRunLine $payrollRunLine): bool
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
}

// LLM-CHECKPOINT
