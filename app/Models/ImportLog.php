<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory;

    // Log types
    public const LOG_JOB_CREATED = 'job_created';
    public const LOG_JOB_STARTED = 'job_started';
    public const LOG_JOB_COMPLETED = 'job_completed';
    public const LOG_JOB_FAILED = 'job_failed';
    public const LOG_FILE_UPLOADED = 'file_uploaded';
    public const LOG_FILE_PARSED = 'file_parsed';
    public const LOG_PARSING_ERROR = 'parsing_error';
    public const LOG_MAPPING_APPLIED = 'mapping_applied';
    public const LOG_MAPPING_FAILED = 'mapping_failed';
    public const LOG_AUTO_MAPPING = 'auto_mapping';
    public const LOG_VALIDATION_STARTED = 'validation_started';
    public const LOG_VALIDATION_PASSED = 'validation_passed';
    public const LOG_VALIDATION_FAILED = 'validation_failed';
    public const LOG_TRANSFORMATION_APPLIED = 'transformation_applied';
    public const LOG_TRANSFORMATION_FAILED = 'transformation_failed';
    public const LOG_DUPLICATE_DETECTED = 'duplicate_detected';
    public const LOG_DUPLICATE_RESOLVED = 'duplicate_resolved';
    public const LOG_RECORD_COMMITTED = 'record_committed';
    public const LOG_RECORD_FAILED = 'record_failed';
    public const LOG_ROLLBACK_EXECUTED = 'rollback_executed';
    public const LOG_CUSTOM_RULE_APPLIED = 'custom_rule_applied';
    public const LOG_BUSINESS_RULE_VIOLATION = 'business_rule_violation';
    public const LOG_PERFORMANCE_WARNING = 'performance_warning';
    public const LOG_SYSTEM_ERROR = 'system_error';

    // Severity levels
    public const SEVERITY_DEBUG = 'debug';
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';

    // Entity types
    public const ENTITY_CUSTOMER = 'customer';
    public const ENTITY_INVOICE = 'invoice';
    public const ENTITY_ITEM = 'item';
    public const ENTITY_PAYMENT = 'payment';
    public const ENTITY_EXPENSE = 'expense';

    protected $guarded = ['id'];

    protected $appends = [
        'formattedCreatedAt',
        'formattedProcessingTime',
        'isError',
        'hasSuggestedFixes',
        'formattedMemoryUsage',
    ];

    protected function casts(): array
    {
        return [
            'error_context' => 'array',
            'suggested_fixes' => 'array',
            'original_data' => 'array',
            'intermediate_data' => 'array',
            'final_data' => 'array',
            'compliance_tags' => 'array',
            'confidence_score' => 'decimal:2',
            'processing_time' => 'decimal:3',
            'throughput_rate' => 'decimal:2',
            'row_number' => 'integer',
            'entity_id' => 'integer',
            'memory_usage' => 'integer',
            'records_processed' => 'integer',
            'is_audit_required' => 'boolean',
            'retention_until' => 'datetime',
        ];
    }

    // Relationships
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function mappingRule(): BelongsTo
    {
        return $this->belongsTo(MappingRule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id ?? null);
        return Carbon::parse($this->created_at)->translatedFormat($dateFormat . ' H:i:s');
    }

    public function getFormattedProcessingTimeAttribute()
    {
        if (!$this->processing_time) {
            return null;
        }

        if ($this->processing_time < 1) {
            return round($this->processing_time * 1000, 2) . 'ms';
        }

        return round($this->processing_time, 3) . 's';
    }

    public function getIsErrorAttribute()
    {
        return in_array($this->severity, [self::SEVERITY_ERROR, self::SEVERITY_CRITICAL]);
    }

    public function getHasSuggestedFixesAttribute()
    {
        return !empty($this->suggested_fixes) && is_array($this->suggested_fixes) && count($this->suggested_fixes) > 0;
    }

    public function getFormattedMemoryUsageAttribute()
    {
        if (!$this->memory_usage) {
            return null;
        }

        return $this->formatBytes($this->memory_usage);
    }

    // Scopes
    public function scopeWhereImportJob($query, $importJobId)
    {
        return $query->where('import_job_id', $importJobId);
    }

    public function scopeWhereLogType($query, $logType)
    {
        return $query->where('log_type', $logType);
    }

    public function scopeWhereSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeWhereEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeWhereEntityId($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    public function scopeWhereRowNumber($query, $rowNumber)
    {
        return $query->where('row_number', $rowNumber);
    }

    public function scopeWhereProcessStage($query, $processStage)
    {
        return $query->where('process_stage', $processStage);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_ERROR, self::SEVERITY_CRITICAL]);
    }

    public function scopeWarnings($query)
    {
        return $query->where('severity', self::SEVERITY_WARNING);
    }

    public function scopeInfoLogs($query)
    {
        return $query->where('severity', self::SEVERITY_INFO);
    }

    public function scopeDebugLogs($query)
    {
        return $query->where('severity', self::SEVERITY_DEBUG);
    }

    public function scopeAuditRequired($query)
    {
        return $query->where('is_audit_required', true);
    }

    public function scopeRetentionExpired($query)
    {
        return $query->where('retention_until', '<', now());
    }

    public function scopeWithSuggestedFixes($query)
    {
        return $query->whereNotNull('suggested_fixes');
    }

    public function scopeLogsBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('log_type')) {
            $query->whereLogType($filters->get('log_type'));
        }

        if ($filters->get('severity')) {
            $query->whereSeverity($filters->get('severity'));
        }

        if ($filters->get('entity_type')) {
            $query->whereEntityType($filters->get('entity_type'));
        }

        if ($filters->get('entity_id')) {
            $query->whereEntityId($filters->get('entity_id'));
        }

        if ($filters->get('row_number')) {
            $query->whereRowNumber($filters->get('row_number'));
        }

        if ($filters->get('process_stage')) {
            $query->whereProcessStage($filters->get('process_stage'));
        }

        if ($filters->get('error_code')) {
            $query->where('error_code', $filters->get('error_code'));
        }

        if ($filters->get('rule_applied')) {
            $query->where('rule_applied', 'LIKE', '%' . $filters->get('rule_applied') . '%');
        }

        if ($filters->get('message')) {
            $query->where(function ($q) use ($filters) {
                $q->where('message', 'LIKE', '%' . $filters->get('message') . '%')
                  ->orWhere('detailed_message', 'LIKE', '%' . $filters->get('message') . '%');
            });
        }

        if ($filters->get('is_audit_required') !== null) {
            $isAuditRequired = filter_var($filters->get('is_audit_required'), FILTER_VALIDATE_BOOLEAN);
            if ($isAuditRequired) {
                $query->auditRequired();
            }
        }

        if ($filters->get('has_suggested_fixes') !== null) {
            $hasSuggestedFixes = filter_var($filters->get('has_suggested_fixes'), FILTER_VALIDATE_BOOLEAN);
            if ($hasSuggestedFixes) {
                $query->withSuggestedFixes();
            }
        }

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'))->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'))->endOfDay();
            $query->logsBetween($start, $end);
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ?: 'created_at';
            $orderBy = $filters->get('orderBy') ?: 'desc';
            $query->orderBy($field, $orderBy);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    // Static factory methods for common log types
    public static function logJobCreated(ImportJob $job, $user = null)
    {
        return self::create([
            'import_job_id' => $job->id,
            'log_type' => self::LOG_JOB_CREATED,
            'severity' => self::SEVERITY_INFO,
            'message' => "Import job '{$job->name}' created",
            'detailed_message' => "New import job created for {$job->type} import from {$job->source_system}",
            'user_id' => $user ? $user->id : null,
            'user_action' => 'job_creation',
        ]);
    }

    public static function logJobStarted(ImportJob $job)
    {
        return self::create([
            'import_job_id' => $job->id,
            'log_type' => self::LOG_JOB_STARTED,
            'severity' => self::SEVERITY_INFO,
            'message' => "Import job '{$job->name}' started",
            'detailed_message' => "Processing started for {$job->total_records} records",
            'process_stage' => 'parsing',
        ]);
    }

    public static function logJobCompleted(ImportJob $job)
    {
        return self::create([
            'import_job_id' => $job->id,
            'log_type' => self::LOG_JOB_COMPLETED,
            'severity' => self::SEVERITY_INFO,
            'message' => "Import job '{$job->name}' completed successfully",
            'detailed_message' => "Processed {$job->processed_records} records with {$job->successful_records} successful and {$job->failed_records} failed",
            'records_processed' => $job->processed_records,
            'processing_time' => $job->started_at ? $job->started_at->diffInSeconds($job->completed_at) : null,
        ]);
    }

    public static function logJobFailed(ImportJob $job, $errorMessage, $errorDetails = null)
    {
        return self::create([
            'import_job_id' => $job->id,
            'log_type' => self::LOG_JOB_FAILED,
            'severity' => self::SEVERITY_ERROR,
            'message' => "Import job '{$job->name}' failed",
            'detailed_message' => $errorMessage,
            'error_context' => $errorDetails,
            'is_audit_required' => true,
        ]);
    }

    public static function logMappingApplied(ImportJob $job, MappingRule $rule, $sourceField, $targetField, $originalValue, $transformedValue, $confidence = null)
    {
        return self::create([
            'import_job_id' => $job->id,
            'mapping_rule_id' => $rule->id,
            'log_type' => self::LOG_MAPPING_APPLIED,
            'severity' => self::SEVERITY_INFO,
            'message' => "Mapping rule applied: {$sourceField} → {$targetField}",
            'detailed_message' => "Applied mapping rule '{$rule->name}' with transformation type '{$rule->transformation_type}'",
            'field_name' => $sourceField,
            'field_value' => $originalValue,
            'transformed_value' => $transformedValue,
            'process_stage' => 'mapping',
            'rule_applied' => $rule->name,
            'confidence_score' => $confidence ?? $rule->confidence_score,
        ]);
    }

    public static function logValidationFailed(ImportJob $job, $entityType, $entityId, $rowNumber, $fieldName, $fieldValue, $validationErrors)
    {
        return self::create([
            'import_job_id' => $job->id,
            'log_type' => self::LOG_VALIDATION_FAILED,
            'severity' => self::SEVERITY_WARNING,
            'message' => "Validation failed for {$fieldName} in row {$rowNumber}",
            'detailed_message' => "Validation errors: " . implode(', ', $validationErrors),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'row_number' => $rowNumber,
            'field_name' => $fieldName,
            'field_value' => $fieldValue,
            'process_stage' => 'validating',
            'error_context' => ['validation_errors' => $validationErrors],
        ]);
    }

    public static function logDuplicateDetected(ImportJob $job, $entityType, $entityId, $rowNumber, $matchField, $existingEntityId = null)
    {
        return self::create([
            'import_job_id' => $job->id,
            'log_type' => self::LOG_DUPLICATE_DETECTED,
            'severity' => self::SEVERITY_WARNING,
            'message' => "Duplicate {$entityType} detected in row {$rowNumber}",
            'detailed_message' => "Duplicate detected based on {$matchField} field" . ($existingEntityId ? " (existing ID: {$existingEntityId})" : ''),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'row_number' => $rowNumber,
            'field_name' => $matchField,
            'process_stage' => 'mapping',
            'error_context' => [
                'match_field' => $matchField,
                'existing_entity_id' => $existingEntityId,
            ],
        ]);
    }

    public static function logTransformationFailed(ImportJob $job, MappingRule $rule, $sourceField, $originalValue, $error)
    {
        return self::create([
            'import_job_id' => $job->id,
            'mapping_rule_id' => $rule->id,
            'log_type' => self::LOG_TRANSFORMATION_FAILED,
            'severity' => self::SEVERITY_ERROR,
            'message' => "Transformation failed for field {$sourceField}",
            'detailed_message' => "Failed to apply transformation rule '{$rule->name}': {$error}",
            'field_name' => $sourceField,
            'field_value' => $originalValue,
            'process_stage' => 'mapping',
            'rule_applied' => $rule->name,
            'error_context' => ['transformation_error' => $error],
            'suggested_fixes' => [
                'Check transformation configuration',
                'Verify input data format',
                'Review mapping rule conditions',
            ],
        ]);
    }

    public static function logPerformanceWarning(ImportJob $job, $message, $processingTime, $memoryUsage, $recordsProcessed)
    {
        return self::create([
            'import_job_id' => $job->id,
            'log_type' => self::LOG_PERFORMANCE_WARNING,
            'severity' => self::SEVERITY_WARNING,
            'message' => $message,
            'detailed_message' => "Performance threshold exceeded during import processing",
            'processing_time' => $processingTime,
            'memory_usage' => $memoryUsage,
            'records_processed' => $recordsProcessed,
            'throughput_rate' => $recordsProcessed > 0 ? $recordsProcessed / $processingTime : 0,
            'suggested_fixes' => [
                'Consider processing in smaller batches',
                'Optimize database queries',
                'Review memory usage patterns',
            ],
        ]);
    }

    public static function logSystemError(ImportJob $job, $error, $stackTrace = null)
    {
        return self::create([
            'import_job_id' => $job->id,
            'log_type' => self::LOG_SYSTEM_ERROR,
            'severity' => self::SEVERITY_CRITICAL,
            'message' => 'System error occurred during import',
            'detailed_message' => $error,
            'stack_trace' => $stackTrace,
            'is_audit_required' => true,
            'retention_until' => now()->addYears(7), // Long retention for critical errors
        ]);
    }

    // Helper methods
    public function markForAudit($retentionYears = 7)
    {
        $this->update([
            'is_audit_required' => true,
            'retention_until' => now()->addYears($retentionYears),
        ]);
    }

    public function addSuggestedFix($fix)
    {
        $fixes = $this->suggested_fixes ?: [];
        $fixes[] = $fix;
        $this->update(['suggested_fixes' => $fixes]);
    }

    public function addComplianceTag($tag)
    {
        $tags = $this->compliance_tags ?: [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['compliance_tags' => $tags]);
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public static function getLogTypeOptions()
    {
        return [
            self::LOG_JOB_CREATED => 'Job Created',
            self::LOG_JOB_STARTED => 'Job Started',
            self::LOG_JOB_COMPLETED => 'Job Completed',
            self::LOG_JOB_FAILED => 'Job Failed',
            self::LOG_FILE_UPLOADED => 'File Uploaded',
            self::LOG_FILE_PARSED => 'File Parsed',
            self::LOG_PARSING_ERROR => 'Parsing Error',
            self::LOG_MAPPING_APPLIED => 'Mapping Applied',
            self::LOG_MAPPING_FAILED => 'Mapping Failed',
            self::LOG_AUTO_MAPPING => 'Auto Mapping',
            self::LOG_VALIDATION_STARTED => 'Validation Started',
            self::LOG_VALIDATION_PASSED => 'Validation Passed',
            self::LOG_VALIDATION_FAILED => 'Validation Failed',
            self::LOG_TRANSFORMATION_APPLIED => 'Transformation Applied',
            self::LOG_TRANSFORMATION_FAILED => 'Transformation Failed',
            self::LOG_DUPLICATE_DETECTED => 'Duplicate Detected',
            self::LOG_DUPLICATE_RESOLVED => 'Duplicate Resolved',
            self::LOG_RECORD_COMMITTED => 'Record Committed',
            self::LOG_RECORD_FAILED => 'Record Failed',
            self::LOG_ROLLBACK_EXECUTED => 'Rollback Executed',
            self::LOG_CUSTOM_RULE_APPLIED => 'Custom Rule Applied',
            self::LOG_BUSINESS_RULE_VIOLATION => 'Business Rule Violation',
            self::LOG_PERFORMANCE_WARNING => 'Performance Warning',
            self::LOG_SYSTEM_ERROR => 'System Error',
        ];
    }

    public static function getSeverityOptions()
    {
        return [
            self::SEVERITY_DEBUG => 'Debug',
            self::SEVERITY_INFO => 'Info',
            self::SEVERITY_WARNING => 'Warning',
            self::SEVERITY_ERROR => 'Error',
            self::SEVERITY_CRITICAL => 'Critical',
        ];
    }

    public static function getEntityTypeOptions()
    {
        return [
            self::ENTITY_CUSTOMER => 'Customer',
            self::ENTITY_INVOICE => 'Invoice',
            self::ENTITY_ITEM => 'Item',
            self::ENTITY_PAYMENT => 'Payment',
            self::ENTITY_EXPENSE => 'Expense',
        ];
    }
}