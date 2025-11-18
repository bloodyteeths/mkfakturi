<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportContact extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'name',
        'email',
        'company_name',
        'subject',
        'category',
        'priority',
        'message',
        'attachments',
        'status',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attachments' => 'array',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that submitted the contact.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company associated with the contact.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include new contacts.
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope a query to only include in progress contacts.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include resolved contacts.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Mark the contact as in progress.
     */
    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    /**
     * Mark the contact as resolved.
     */
    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Get the reference number for this contact.
     */
    public function getReferenceNumberAttribute(): string
    {
        return 'SUP-'.str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted category name.
     */
    public function getCategoryNameAttribute(): string
    {
        $categories = [
            'technical' => 'Technical Issue',
            'billing' => 'Billing Question',
            'feature' => 'Feature Request',
            'general' => 'General Inquiry',
        ];

        return $categories[$this->category] ?? $this->category;
    }

    /**
     * Get formatted priority name.
     */
    public function getPriorityNameAttribute(): string
    {
        return ucfirst($this->priority);
    }
}
// CLAUDE-CHECKPOINT
