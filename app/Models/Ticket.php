<?php

namespace App\Models;

use Coderflex\LaravelTicket\Models\Ticket as BaseTicket;

/**
 * Custom Ticket model extending the LaravelTicket package model.
 * Adds company relationship for multi-tenant support.
 */
class Ticket extends BaseTicket
{
    /**
     * Get the company that owns the ticket.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
// CLAUDE-CHECKPOINT
