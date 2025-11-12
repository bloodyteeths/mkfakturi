<?php

namespace App\Policies;

use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportJobPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any import jobs.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Use the user instance to check abilities (respects Bouncer scope)
        if ($user->can('view-import-job', ImportJob::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the import job.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return bool
     */
    public function view(User $user, ImportJob $importJob): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Check if user has permission and belongs to the same company
        if ($user->can('view-import-job', $importJob) && $user->hasCompany($importJob->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create import jobs.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Check if user has permission to create import jobs
        if ($user->can('create-import-job', ImportJob::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the import job.
     * This includes updating mapping configuration and validation rules.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return bool
     */
    public function update(User $user, ImportJob $importJob): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Check if user has permission and belongs to the same company
        if ($user->can('edit-import-job', $importJob) && $user->hasCompany($importJob->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the import job.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return bool
     */
    public function delete(User $user, ImportJob $importJob): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Check if user has permission and belongs to the same company
        if ($user->can('delete-import-job', $importJob) && $user->hasCompany($importJob->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the import job.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return bool
     */
    public function restore(User $user, ImportJob $importJob): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Check if user has permission and belongs to the same company
        if ($user->can('delete-import-job', $importJob) && $user->hasCompany($importJob->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the import job.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return bool
     */
    public function forceDelete(User $user, ImportJob $importJob): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Check if user has permission and belongs to the same company
        if ($user->can('delete-import-job', $importJob) && $user->hasCompany($importJob->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can commit the import to production.
     * This is a critical action that transforms temporary data into production data.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return bool
     */
    public function commit(User $user, ImportJob $importJob): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Check if user has permission to commit imports and belongs to the same company
        if ($user->can('commit-import-job', $importJob) && $user->hasCompany($importJob->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete multiple import jobs.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function deleteMultiple(User $user): bool
    {
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Check if user has permission to delete import jobs
        if ($user->can('delete-import-job', ImportJob::class)) {
            return true;
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
