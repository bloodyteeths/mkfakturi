<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Silber\Bouncer\BouncerFacade;

class NotePolicy
{
    use HandlesAuthorization;

    public function manageNotes(User $user)
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('manage-all-notes', Note::class)) {
            return true;
        }

        return false;
    }

    public function viewNotes(User $user)
    {
        if ($user->isOwner()) {
            return true;
        }
        
        if ($user->can('view-all-notes', Note::class)) {
            return true;
        }

        return false;
    }
}
