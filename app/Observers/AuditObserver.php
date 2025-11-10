<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * AuditObserver
 *
 * Automatically logs all model changes to the audit_logs table.
 * Captures before/after values, user context, and request metadata.
 *
 * This observer is registered by the HasAuditing trait.
 *
 * @package App\Observers
 */
class AuditObserver
{
    /**
     * Batch ID for grouping related operations.
     *
     * @var string|null
     */
    protected static ?string $batchId = null;

    /**
     * Handle the Model "creating" event.
     * Sets created_by and updated_by before the model is saved.
     *
     * @param Model $model
     * @return void
     */
    public function creating(Model $model): void
    {
        // Set audit user fields if model has HasAuditing trait
        if (method_exists($model, 'setAuditUser')) {
            $model->setAuditUser();
        }
    }

    /**
     * Handle the Model "created" event.
     *
     * @param Model $model
     * @return void
     */
    public function created(Model $model): void
    {
        $this->logChange($model, 'created');
    }

    /**
     * Handle the Model "updating" event.
     * Sets updated_by before the model is saved.
     *
     * @param Model $model
     * @return void
     */
    public function updating(Model $model): void
    {
        // Set audit user fields if model has HasAuditing trait
        if (method_exists($model, 'setAuditUser')) {
            $model->setAuditUser();
        }
    }

    /**
     * Handle the Model "updated" event.
     *
     * @param Model $model
     * @return void
     */
    public function updated(Model $model): void
    {
        // Only log if there were actual changes
        if ($model->wasChanged() && count($model->getChanges()) > 0) {
            $this->logChange($model, 'updated');
        }
    }

    /**
     * Handle the Model "deleted" event.
     *
     * @param Model $model
     * @return void
     */
    public function deleted(Model $model): void
    {
        $this->logChange($model, 'deleted');
    }

    /**
     * Handle the Model "restored" event.
     *
     * @param Model $model
     * @return void
     */
    public function restored(Model $model): void
    {
        $this->logChange($model, 'restored');
    }

    /**
     * Handle the Model "forceDeleted" event.
     *
     * @param Model $model
     * @return void
     */
    public function forceDeleted(Model $model): void
    {
        $this->logChange($model, 'force_deleted');
    }

    /**
     * Log the model change to the audit_logs table.
     *
     * @param Model $model
     * @param string $event
     * @return void
     */
    protected function logChange(Model $model, string $event): void
    {
        try {
            $user = Auth::user();
            $request = request();

            // Get company_id from model or request header
            $companyId = $this->getCompanyId($model);

            // Get original and new values
            $oldValues = $this->getOldValues($model, $event);
            $newValues = $this->getNewValues($model, $event);

            // Filter excluded fields
            $excludedFields = method_exists($model, 'getAuditExclude')
                ? $model->getAuditExclude()
                : [];

            $oldValues = $this->filterExcludedFields($oldValues, $excludedFields);
            $newValues = $this->filterExcludedFields($newValues, $excludedFields);

            // Encrypt PII fields
            $oldValues = AuditLog::encryptPii($oldValues);
            $newValues = AuditLog::encryptPii($newValues);

            // Calculate changed fields
            $changedFields = $this->getChangedFields($oldValues, $newValues);

            // Generate batch ID if in batch operation
            $batchId = $this->getBatchId();

            // Build description
            $description = $this->buildDescription($model, $event);

            // Create audit log
            AuditLog::create([
                'company_id' => $companyId,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System',
                'event' => $event,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'changed_fields' => $changedFields,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'url' => $request?->fullUrl(),
                'http_method' => $request?->method(),
                'batch_id' => $batchId,
                'tags' => $this->getTags($model, $event),
            ]);
        } catch (\Exception $e) {
            // Log error but don't throw - we don't want to block the operation
            \Log::error('AuditObserver: Failed to log change', [
                'model' => get_class($model),
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get company_id from model or request.
     *
     * @param Model $model
     * @return int|null
     */
    protected function getCompanyId(Model $model): ?int
    {
        // Try to get from model's company_id attribute
        if (isset($model->company_id)) {
            return $model->company_id;
        }

        // Try to get from request header
        $companyId = request()->header('company');

        return $companyId ? (int) $companyId : null;
    }

    /**
     * Get old values before change.
     *
     * @param Model $model
     * @param string $event
     * @return array
     */
    protected function getOldValues(Model $model, string $event): array
    {
        if (in_array($event, ['created'])) {
            return [];
        }

        if (in_array($event, ['deleted', 'force_deleted'])) {
            return $model->getAttributes();
        }

        // For updates, return original values of changed fields
        if ($event === 'updated') {
            $changes = $model->getChanges();
            $original = $model->getOriginal();

            return array_intersect_key($original, $changes);
        }

        return $model->getOriginal();
    }

    /**
     * Get new values after change.
     *
     * @param Model $model
     * @param string $event
     * @return array
     */
    protected function getNewValues(Model $model, string $event): array
    {
        if (in_array($event, ['deleted', 'force_deleted'])) {
            return [];
        }

        if ($event === 'updated') {
            return $model->getChanges();
        }

        return $model->getAttributes();
    }

    /**
     * Filter out excluded fields from values.
     *
     * @param array $values
     * @param array $excludedFields
     * @return array
     */
    protected function filterExcludedFields(array $values, array $excludedFields): array
    {
        // Always exclude these fields
        $alwaysExclude = ['password', 'remember_token', 'api_token'];
        $excludedFields = array_merge($excludedFields, $alwaysExclude);

        return array_diff_key($values, array_flip($excludedFields));
    }

    /**
     * Get list of changed field names.
     *
     * @param array $oldValues
     * @param array $newValues
     * @return array
     */
    protected function getChangedFields(array $oldValues, array $newValues): array
    {
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
        $changedFields = [];

        foreach ($allKeys as $key) {
            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changedFields[] = $key;
            }
        }

        return $changedFields;
    }

    /**
     * Build human-readable description of the change.
     *
     * @param Model $model
     * @param string $event
     * @return string
     */
    protected function buildDescription(Model $model, string $event): string
    {
        $modelName = class_basename($model);
        $identifier = $this->getModelIdentifier($model);

        $eventDescriptions = [
            'created' => "Created {$modelName} {$identifier}",
            'updated' => "Updated {$modelName} {$identifier}",
            'deleted' => "Deleted {$modelName} {$identifier}",
            'restored' => "Restored {$modelName} {$identifier}",
            'force_deleted' => "Force deleted {$modelName} {$identifier}",
        ];

        return $eventDescriptions[$event] ?? "{$event} {$modelName} {$identifier}";
    }

    /**
     * Get a human-readable identifier for the model.
     *
     * @param Model $model
     * @return string
     */
    protected function getModelIdentifier(Model $model): string
    {
        // Try common identifier fields
        $identifierFields = ['name', 'title', 'invoice_number', 'payment_number', 'email', 'id'];

        foreach ($identifierFields as $field) {
            if (isset($model->$field)) {
                return "'{$model->$field}'";
            }
        }

        return "#{$model->getKey()}";
    }

    /**
     * Get or generate batch ID for grouping operations.
     *
     * @return string|null
     */
    protected function getBatchId(): ?string
    {
        // Return existing batch ID if set
        if (static::$batchId) {
            return static::$batchId;
        }

        // Check if this is a batch operation (multiple models in one request)
        // For now, we don't auto-generate batch IDs
        return null;
    }

    /**
     * Start a batch operation.
     * All changes will be grouped under the same batch_id.
     *
     * @return string
     */
    public static function startBatch(): string
    {
        static::$batchId = (string) Str::uuid();

        return static::$batchId;
    }

    /**
     * End a batch operation.
     *
     * @return void
     */
    public static function endBatch(): void
    {
        static::$batchId = null;
    }

    /**
     * Get tags for the audit log entry.
     *
     * @param Model $model
     * @param string $event
     * @return array
     */
    protected function getTags(Model $model, string $event): array
    {
        $tags = [
            'model' => class_basename($model),
            'event' => $event,
        ];

        // Add custom tags if model defines them
        if (method_exists($model, 'getAuditTags')) {
            $customTags = $model->getAuditTags($event);
            $tags = array_merge($tags, $customTags);
        }

        return $tags;
    }
}

// CLAUDE-CHECKPOINT
