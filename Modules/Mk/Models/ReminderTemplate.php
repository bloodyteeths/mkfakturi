<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderTemplate extends Model
{
    protected $table = 'reminder_templates';

    protected $fillable = [
        'company_id',
        'escalation_level',
        'days_after_due',
        'subject_mk',
        'subject_en',
        'subject_tr',
        'subject_sq',
        'body_mk',
        'body_en',
        'body_tr',
        'body_sq',
        'is_active',
        'auto_send',
    ];

    protected function casts(): array
    {
        return [
            'days_after_due' => 'integer',
            'is_active' => 'boolean',
            'auto_send' => 'boolean',
        ];
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ---- Scopes ----

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('reminder_templates.company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('reminder_templates.is_active', true);
    }

    // ---- Helpers ----

    /**
     * Get the subject for a given locale.
     */
    public function getSubjectForLocale(string $locale = 'mk'): string
    {
        $field = 'subject_' . $locale;

        return $this->{$field} ?? $this->subject_mk;
    }

    /**
     * Get the body for a given locale.
     */
    public function getBodyForLocale(string $locale = 'mk'): string
    {
        $field = 'body_' . $locale;

        return $this->{$field} ?? $this->body_mk;
    }
}

// CLAUDE-CHECKPOINT
