<?php

namespace App\Policies;

use App\Models\ClientDocument;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientDocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any documents.
     * User must belong to the company OR be a partner managing the company.
     */
    public function viewAny(User $user, int $companyId): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Regular user: must belong to the company
        if ($user->hasCompany($companyId)) {
            return true;
        }

        // Partner: must manage the company
        if ($user->role === 'partner') {
            return $this->partnerManagesCompany($user, $companyId);
        }

        return false;
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, ClientDocument $document): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        // Regular user: must belong to the document's company
        if ($user->hasCompany($document->company_id)) {
            return true;
        }

        // Partner: must manage the document's company
        if ($user->role === 'partner') {
            return $this->partnerManagesCompany($user, $document->company_id);
        }

        return false;
    }

    /**
     * Determine whether the user can create documents.
     * Only users belonging to the company can upload.
     */
    public function create(User $user, int $companyId): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $user->hasCompany($companyId);
    }

    /**
     * Determine whether the user can delete the document.
     * Only users in the company AND only if status is pending_review.
     */
    public function delete(User $user, ClientDocument $document): bool
    {
        if ($user->isOwner() && $document->isPending()) {
            return true;
        }

        if ($user->hasCompany($document->company_id) && $document->isPending()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can review (mark as reviewed) the document.
     * Only partners managing the company can review.
     */
    public function review(User $user, ClientDocument $document): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $this->partnerManagesCompany($user, $document->company_id);
        }

        return false;
    }

    /**
     * Determine whether the user can reject the document.
     * Only partners managing the company can reject.
     */
    public function reject(User $user, ClientDocument $document): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($user->role === 'partner') {
            return $this->partnerManagesCompany($user, $document->company_id);
        }

        return false;
    }

    /**
     * Check if the user is a partner that manages the given company.
     */
    private function partnerManagesCompany(User $user, int $companyId): bool
    {
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner || ! $partner->is_active) {
            return false;
        }

        return $partner->activeCompanies()
            ->where('companies.id', $companyId)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
