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
