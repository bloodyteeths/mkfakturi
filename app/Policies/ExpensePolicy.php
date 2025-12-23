<?php

namespace App\Policies;

use App\Enums\PartnerPermission;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpensePolicy
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

        if ($user->role === 'partner') {
            $companyId = (int) request()->header('company');
            return $user->hasPartnerPermission($companyId, PartnerPermission::VIEW_EXPENSES);
        }

        if ($user->can('view-expense', Expense::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Expense $expense): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($expense->company_id, PartnerPermission::VIEW_EXPENSES);
        }

        if ($user->can('view-expense', $expense) && $user->hasCompany($expense->company_id)) {
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

        if ($user->role === 'partner') {
            $companyId = (int) request()->header('company');
            return $user->hasPartnerPermission($companyId, PartnerPermission::CREATE_EXPENSES);
        }

        if ($user->can('create-expense', Expense::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Expense $expense): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($expense->company_id, PartnerPermission::EDIT_EXPENSES);
        }

        if ($user->can('edit-expense', $expense) && $user->hasCompany($expense->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Expense $expense): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($expense->company_id, PartnerPermission::DELETE_EXPENSES);
        }

        if ($user->can('delete-expense', $expense) && $user->hasCompany($expense->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, Expense $expense): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($expense->company_id, PartnerPermission::DELETE_EXPENSES);
        }

        if ($user->can('delete-expense', $expense) && $user->hasCompany($expense->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Expense $expense): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $user->hasPartnerPermission($expense->company_id, PartnerPermission::DELETE_EXPENSES);
        }

        if ($user->can('delete-expense', $expense) && $user->hasCompany($expense->company_id)) {
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

        if ($user->role === 'partner') {
            $companyId = (int) request()->header('company');
            return $user->hasPartnerPermission($companyId, PartnerPermission::DELETE_EXPENSES);
        }

        if ($user->can('delete-expense', Expense::class)) {
            return true;
        }

        return false;
    }
}
// CLAUDE-CHECKPOINT
