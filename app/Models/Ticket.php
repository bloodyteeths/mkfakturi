<?php

namespace App\Models;

use Coderflex\LaravelTicket\Models\Ticket as BaseTicket;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Custom Ticket model extending the LaravelTicket package model.
 * Adds company relationship for multi-tenant support.
 * Overrides messages relationship to use custom TicketMessage model with media support.
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

    /**
     * Override messages relationship to use our custom TicketMessage model.
     * This enables Spatie Media Library support for message attachments.
     */
    public function messages(): HasMany
    {
        $tableName = config('laravel_ticket.table_names.messages', 'messages');

        return $this->hasMany(
            TicketMessage::class,
            (string) $tableName['columns']['ticket_foreign_id'],
        );
    }
}
// CLAUDE-CHECKPOINT
