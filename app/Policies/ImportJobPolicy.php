<?php

namespace App\Policies;

use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportJobPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // For now, allow any authenticated user.
        // In the future, this could be restricted to admins or specific roles.
        return $user->id > 0;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ImportJob $importJob)
    {
        // Users can only view their own import jobs.
        return $user->id === $importJob->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Any authenticated user can create an import job.
        return $user->id > 0;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ImportJob $importJob)
    {
        // Users can only update their own import jobs.
        return $user->id === $importJob->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ImportJob $importJob)
    {
        // Users can only delete their own import jobs.
        return $user->id === $importJob->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ImportJob $importJob)
    {
        return false; // Not implemented
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportJob  $importJob
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ImportJob $importJob)
    {
        return false; // Not implemented
    }
}