<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

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
        // Check if user is owner - owners have all permissions
        if ($user->isOwner()) {
            return true;
        }

        // Partners can view projects
        if ($user->role === 'partner') {
            return true;
        }

        // Use the user instance to check abilities (respects Bouncer scope)
        return $user->can('view-project', Project::class);
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return true;
        }

        return $user->can('view-project', $project);
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return true;
        }

        return $user->can('create-project', Project::class);
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return true;
        }

        return $user->can('edit-project', $project);
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return true;
        }

        return $user->can('delete-project', $project);
    }

    /**
     * Determine whether the user can bulk delete projects.
     */
    public function deleteMultiple(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return true;
        }

        return $user->can('delete-project', Project::class);
    }

    /**
     * Determine whether the user can restore the project.
     */
    public function restore(User $user, Project $project): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $user->can('delete-project', $project);
    }

    /**
     * Determine whether the user can permanently delete the project.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $user->can('delete-project', $project);
    }
}

// CLAUDE-CHECKPOINT
