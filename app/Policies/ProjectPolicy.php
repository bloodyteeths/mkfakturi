<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Silber\Bouncer\BouncerFacade;

/**
 * Project Policy
 *
 * Authorization policies for project management.
 * Part of Phase 1.1 - Project Dimension feature for accountants.
 */
class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        if (BouncerFacade::can('view-all-projects', Project::class)) {
            return true;
        }

        return BouncerFacade::can('view-project', Project::class);
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        if (BouncerFacade::can('view-all-projects', Project::class)) {
            return true;
        }

        return BouncerFacade::can('view-project', Project::class);
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return BouncerFacade::can('create-project', Project::class);
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        return BouncerFacade::can('edit-project', Project::class);
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return BouncerFacade::can('delete-project', Project::class);
    }

    /**
     * Determine whether the user can bulk delete projects.
     */
    public function deleteMultiple(User $user): bool
    {
        return BouncerFacade::can('delete-project', Project::class);
    }

    /**
     * Determine whether the user can restore the project.
     */
    public function restore(User $user, Project $project): bool
    {
        return BouncerFacade::can('delete-project', Project::class);
    }

    /**
     * Determine whether the user can permanently delete the project.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return BouncerFacade::can('delete-project', Project::class);
    }
}

// CLAUDE-CHECKPOINT
