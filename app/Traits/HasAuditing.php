<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasAuditing Trait
 *
 * Automatically logs all model changes (create, update, delete) to the audit_logs table.
 * Tracks user_id, IP address, user agent, and before/after values.
 *
 * Usage: Add to any model that needs audit logging:
 *   use HasAuditing;
 *
 * @package App\Traits
 */
trait HasAuditing
{
    /**
     * Boot the HasAuditing trait.
     * Registers the AuditObserver to listen for model events.
     *
     * @return void
     */
    protected static function bootHasAuditing(): void
    {
        static::observe(AuditObserver::class);
    }

    /**
     * Get all audit logs for this model.
     *
     * @return MorphMany
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the user who created this record.
     * Uses the created_by column if it exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function creator()
    {
        if (! in_array('created_by', $this->fillable)) {
            return null;
        }

        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     * Uses the updated_by column if it exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function updater()
    {
        if (! in_array('updated_by', $this->fillable)) {
            return null;
        }

        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Automatically set created_by and updated_by when saving.
     * This method is called by model events in AuditObserver.
     *
     * @return void
     */
    public function setAuditUser(): void
    {
        $userId = auth()->id();

        if (! $userId) {
            return;
        }

        // Set created_by on new records
        if (! $this->exists && in_array('created_by', $this->fillable)) {
            $this->created_by = $userId;
        }

        // Always set updated_by
        if (in_array('updated_by', $this->fillable)) {
            $this->updated_by = $userId;
        }
    }

    /**
     * Get fields that should be excluded from audit logging.
     * Override this method in your model to customize.
     *
     * @return array
     */
    public function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude') ? $this->auditExclude : [];
    }

    /**
     * Determine if a field should be encrypted in audit logs.
     * Override this method in your model to customize.
     *
     * @param string $field
     * @return bool
     */
    public function shouldEncryptAuditField(string $field): bool
    {
        // Use the AuditLog model's PII fields list
        return in_array($field, [
            'vat_id',
            'vat_number',
            'tax_id',
            'iban',
            'bank_account',
            'email',
            'phone',
            'ssn',
        ]);
    }
}

// CLAUDE-CHECKPOINT
