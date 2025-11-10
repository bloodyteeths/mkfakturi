<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any audit logs.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-audit-logs', AuditLog::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the audit log.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AuditLog  $auditLog
     * @return bool
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-audit-logs', $auditLog) && $user->hasCompany($auditLog->company_id)) {
            return true;
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
