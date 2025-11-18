<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    public function viewReport(User $user, Company $company)
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->can('view-financial-reports') && $user->hasCompany($company->id)) {
            return true;
        }

        return false;
    }
}
