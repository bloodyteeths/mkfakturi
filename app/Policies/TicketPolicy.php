<?php

namespace App\Policies;

use App\Models\User;
use Coderflex\LaravelTicket\Models\Ticket;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tickets.
     * CRITICAL: Tenant isolation enforced at controller level via company_id filtering
     *
     * @return mixed
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view their company's tickets
        return true;
    }

    /**
     * Determine whether the user can view the ticket.
     * CRITICAL: This is the PRIMARY tenant isolation check
     *
     * @return mixed
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // CRITICAL: Verify ticket belongs to user's current company
        $companyId = request()->header('company');

        if (! $companyId) {
            return false;
        }

        // User must belong to this company
        if (! $user->hasCompany($companyId)) {
            return false;
        }

        // Ticket must belong to this company (TENANT ISOLATION)
        if ($ticket->company_id != $companyId) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can create tickets.
     *
     * @return mixed
     */
    public function create(User $user): bool
    {
        // All authenticated users can create tickets for their company
        $companyId = request()->header('company');

        if (! $companyId) {
            return false;
        }

        // Verify user belongs to the company
        return $user->hasCompany($companyId);
    }

    /**
     * Determine whether the user can update the ticket.
     *
     * @return mixed
     */
    public function update(User $user, Ticket $ticket): bool
    {
        $companyId = request()->header('company');

        if (! $companyId) {
            return false;
        }

        // User must belong to this company
        if (! $user->hasCompany($companyId)) {
            return false;
        }

        // Ticket must belong to this company (TENANT ISOLATION)
        if ($ticket->company_id != $companyId) {
            return false;
        }

        // Owners can update any ticket, regular users can only update their own
        if ($user->isOwner()) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the ticket.
     *
     * @return mixed
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        $companyId = request()->header('company');

        if (! $companyId) {
            return false;
        }

        // User must belong to this company
        if (! $user->hasCompany($companyId)) {
            return false;
        }

        // Ticket must belong to this company (TENANT ISOLATION)
        if ($ticket->company_id != $companyId) {
            return false;
        }

        // Only owners can delete tickets
        return $user->isOwner();
    }

    /**
     * Determine whether the user can reply to the ticket.
     *
     * @return mixed
     */
    public function reply(User $user, Ticket $ticket): bool
    {
        $companyId = request()->header('company');

        if (! $companyId) {
            return false;
        }

        // User must belong to this company
        if (! $user->hasCompany($companyId)) {
            return false;
        }

        // Ticket must belong to this company (TENANT ISOLATION)
        if ($ticket->company_id != $companyId) {
            return false;
        }

        // Cannot reply to locked tickets
        if ($ticket->is_locked) {
            return false;
        }

        return true;
    }
}
// CLAUDE-CHECKPOINT
