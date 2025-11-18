<?php

namespace App\Jobs\Migration;

use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Models\ImportTempCustomer;
use App\Models\ImportTempExpense;
use App\Models\ImportTempInvoice;
use App\Models\ImportTempItem;
use App\Models\ImportTempPayment;
use App\Models\MappingRule;
use App\Services\Migration\FieldMapperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\QueueableActions\QueueableAction;

/**
 * AutoMapFieldsJob - Auto-map fields using FieldMapperService
 *
 * This job automatically maps source fields to target fields using:
 * - Macedonian language corpus from FieldMapperService
 * - Heuristic matching algorithms
 * - Confidence scoring for mapping accuracy
 * - Learning from previous successful mappings
 * - Creates mapping rules for validated mappings
 */
class AutoMapFieldsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, QueueableAction, SerializesModels;

    public ImportJob $importJob;

    /**
     * Job timeout in seconds (15 minutes)
     */
    public int $timeout = 900;

    /**
     * Maximum number of retries
     */
    public int $tries = 2;

    /**
     * Backoff delays in seconds
     */
    public array $backoff = [30, 120];

    /**
     * Queue name for import jobs
     */
    public string $queue = 'migration';

    /**
     * Minimum confidence threshold for auto-mapping
     */
    protected float $minConfidenceThreshold = 0.7;

    /**
     * High confidence threshold for immediate application
     */
    protected float $highConfidenceThreshold = 0.9;

    /**
     * Create a new job instance
     */
    public function __construct(ImportJob $importJob)
    {
        $this->importJob = $importJob;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            // Update job status
            $this->importJob->update(['status' => ImportJob::STATUS_MAPPING]);

            Log::info('Auto field mapping started', [
                'import_job_id' => $this->importJob->id,
                'import_type' => $this->importJob->type,
            ]);

            // Initialize field mapper service
            $fieldMapper = new FieldMapperService;

            // Extract field headers from parsed data
            $sourceFields = $this->extractSourceFields();

            if (empty($sourceFields)) {
                throw new \Exception('No source fields found to map');
            }

            // Get target field schema for import type
            $targetSchema = $this->getTargetSchema();

            // Perform auto-mapping
            $mappingResults = $this->performAutoMapping($fieldMapper, $sourceFields, $targetSchema);

            // Save mapping configuration to import job
            $this->saveMappingConfiguration($mappingResults);

            // Create mapping rules for high-confidence mappings
            $this->createMappingRules($mappingResults);

            // Apply mappings to temp data
            $this->applyMappingsToTempData($mappingResults);

            // Log mapping results
            $this->logMappingResults($mappingResults, microtime(true) - $startTime);

            Log::info('Auto field mapping completed', [
                'import_job_id' => $this->importJob->id,
                'total_fields' => count($sourceFields),
                'mapped_fields' => count(array_filter($mappingResults, fn ($r) => $r['mapped'])),
                'high_confidence_mappings' => count(array_filter($mappingResults, fn ($r) => $r['confidence'] >= $this->highConfidenceThreshold)),
            ]);

            // Chain to next job - ValidateDataJob
            ValidateDataJob::dispatch($this->importJob)
                ->onQueue('imports')
                ->delay(now()->addSeconds(3));

        } catch (\Exception $e) {
            $this->handleJobFailure($e);
            throw $e;
        }
    }

    /**
     * Extract source field names from parsed data
     */
    protected function extractSourceFields(): array
    {
        $tempModel = $this->getTempModelClass();

        // Get first record to extract field structure
        $firstRecord = $tempModel::where('import_job_id', $this->importJob->id)
            ->first();

        if (! $firstRecord) {
            return [];
        }

        $rawData = json_decode($firstRecord->raw_data, true);

        // Handle different data structures
        if (is_array($rawData)) {
            // For CSV with headers or XML with named elements
            return array_keys($rawData);
        }

        // For indexed arrays (CSV without headers), create generic field names
        if (is_array($rawData)) {
            return array_map(fn ($i) => "column_$i", array_keys($rawData));
        }

        return [];
    }

    /**
     * Get target schema for the import type
     */
    protected function getTargetSchema(): array
    {
        return match ($this->importJob->type) {
            ImportJob::TYPE_CUSTOMERS => [
                'name', 'email', 'phone', 'address_1', 'address_2', 'city', 'state',
                'zip', 'country', 'tax_id', 'website', 'contact_name', 'currency_id',
            ],
            ImportJob::TYPE_INVOICES => [
                'invoice_number', 'customer_id', 'invoice_date', 'due_date',
                'subtotal', 'tax_amount', 'total', 'status', 'notes', 'currency_id',
            ],
            ImportJob::TYPE_ITEMS => [
                'name', 'description', 'price', 'unit', 'tax_type_id',
                'sku', 'category', 'quantity',
            ],
            ImportJob::TYPE_PAYMENTS => [
                'payment_date', 'amount', 'payment_method', 'reference_number',
                'customer_id', 'invoice_id', 'notes', 'currency_id',
            ],
            ImportJob::TYPE_EXPENSES => [
                'expense_date', 'amount', 'category', 'vendor', 'description',
                'payment_method', 'tax_amount', 'receipt_path', 'currency_id',
            ],
            default => [],
        };
    }

    /**
     * Perform auto-mapping using FieldMapperService
     */
    protected function performAutoMapping(FieldMapperService $fieldMapper, array $sourceFields, array $targetSchema): array
    {
        $mappingResults = [];

        foreach ($sourceFields as $sourceField) {
            // Try to map each source field to target schema
            $mappingResult = $fieldMapper->mapField(
                $sourceField,
                $this->importJob->type,
                $targetSchema
            );

            $confidence = $mappingResult['confidence'] ?? 0.0;
            $suggestedTarget = $mappingResult['target_field'] ?? null;
            $transformationType = $mappingResult['transformation_type'] ?? 'direct';

            $mappingResults[$sourceField] = [
                'source_field' => $sourceField,
                'target_field' => $suggestedTarget,
                'confidence' => $confidence,
                'transformation_type' => $transformationType,
                'mapped' => $confidence >= $this->minConfidenceThreshold && $suggestedTarget !== null,
                'auto_applied' => $confidence >= $this->highConfidenceThreshold,
                'suggestions' => $mappingResult['suggestions'] ?? [],
                'mapping_reason' => $mappingResult['reason'] ?? 'heuristic_match',
            ];

            // Log mapping attempt
            ImportLog::create([
                'import_job_id' => $this->importJob->id,
                'log_type' => ImportLog::LOG_AUTO_MAPPING,
                'severity' => $confidence >= $this->minConfidenceThreshold ? ImportLog::SEVERITY_INFO : ImportLog::SEVERITY_WARNING,
                'message' => "Auto-mapping: {$sourceField} → ".($suggestedTarget ?? 'unmapped'),
                'detailed_message' => "Field mapping attempted with {$confidence} confidence using {$mappingResult['reason']} method",
                'field_name' => $sourceField,
                'field_value' => $suggestedTarget,
                'process_stage' => 'mapping',
                'confidence_score' => $confidence,
                'rule_applied' => $mappingResult['rule_name'] ?? 'auto_mapping',
            ]);
        }

        return $mappingResults;
    }

    /**
     * Save mapping configuration to import job
     */
    protected function saveMappingConfiguration(array $mappingResults): void
    {
        $mappingConfig = [
            'auto_mapping_completed' => true,
            'auto_mapping_timestamp' => now()->toISOString(),
            'total_fields' => count($mappingResults),
            'mapped_fields' => count(array_filter($mappingResults, fn ($r) => $r['mapped'])),
            'high_confidence_mappings' => count(array_filter($mappingResults, fn ($r) => $r['confidence'] >= $this->highConfidenceThreshold)),
            'mappings' => $mappingResults,
            'requires_manual_review' => $this->requiresManualReview($mappingResults),
        ];

        $this->importJob->update(['mapping_config' => $mappingConfig]);
    }

    /**
     * Check if mapping requires manual review
     */
    protected function requiresManualReview(array $mappingResults): bool
    {
        $mappedCount = count(array_filter($mappingResults, fn ($r) => $r['mapped']));
        $totalCount = count($mappingResults);

        // Require manual review if less than 70% of fields are mapped
        $mappingRate = $totalCount > 0 ? $mappedCount / $totalCount : 0;

        return $mappingRate < 0.7;
    }

    /**
     * Create mapping rules for high-confidence mappings
     */
    protected function createMappingRules(array $mappingResults): void
    {
        foreach ($mappingResults as $mapping) {
            if ($mapping['confidence'] >= $this->highConfidenceThreshold && $mapping['mapped']) {
                // Check if mapping rule already exists
                $existingRule = MappingRule::where('company_id', $this->importJob->company_id)
                    ->where('source_field', $mapping['source_field'])
                    ->where('target_field', $mapping['target_field'])
                    ->where('entity_type', $this->importJob->type)
                    ->first();

                if (! $existingRule) {
                    $mappingRule = MappingRule::create([
                        'company_id' => $this->importJob->company_id,
                        'name' => "Auto: {$mapping['source_field']} → {$mapping['target_field']}",
                        'source_field' => $mapping['source_field'],
                        'target_field' => $mapping['target_field'],
                        'entity_type' => $this->importJob->type,
                        'transformation_type' => $mapping['transformation_type'],
                        'confidence_score' => $mapping['confidence'],
                        'is_active' => true,
                        'created_by_system' => true,
                        'usage_count' => 1,
                        'success_rate' => 100.0,
                        'last_used_at' => now(),
                        'source_system' => $this->importJob->source_system,
                        'transformation_config' => [
                            'auto_generated' => true,
                            'mapping_reason' => $mapping['mapping_reason'],
                            'original_confidence' => $mapping['confidence'],
                        ],
                    ]);

                    // Log mapping rule creation
                    ImportLog::create([
                        'import_job_id' => $this->importJob->id,
                        'mapping_rule_id' => $mappingRule->id,
                        'log_type' => ImportLog::LOG_CUSTOM_RULE_APPLIED,
                        'severity' => ImportLog::SEVERITY_INFO,
                        'message' => "Created mapping rule: {$mapping['source_field']} → {$mapping['target_field']}",
                        'detailed_message' => "Auto-generated mapping rule with {$mapping['confidence']} confidence",
                        'field_name' => $mapping['source_field'],
                        'field_value' => $mapping['target_field'],
                        'process_stage' => 'mapping',
                        'rule_applied' => $mappingRule->name,
                        'confidence_score' => $mapping['confidence'],
                    ]);
                } else {
                    // Update existing rule statistics
                    $existingRule->increment('usage_count');
                    $existingRule->update(['last_used_at' => now()]);
                }
            }
        }
    }

    /**
     * Apply mappings to temporary data
     */
    protected function applyMappingsToTempData(array $mappingResults): void
    {
        $tempModel = $this->getTempModelClass();
        $highConfidenceMappings = array_filter($mappingResults, fn ($r) => $r['auto_applied']);

        if (empty($highConfidenceMappings)) {
            Log::info('No high-confidence mappings to apply automatically', [
                'import_job_id' => $this->importJob->id,
            ]);

            return;
        }

        // Process records in batches
        $tempModel::where('import_job_id', $this->importJob->id)
            ->chunk(500, function ($records) use ($highConfidenceMappings) {
                foreach ($records as $record) {
                    $this->applyMappingToRecord($record, $highConfidenceMappings);
                }
            });
    }

    /**
     * Apply mapping to a single record
     */
    protected function applyMappingToRecord($record, array $mappings): void
    {
        $rawData = json_decode($record->raw_data, true);
        $mappedData = [];

        foreach ($mappings as $mapping) {
            $sourceField = $mapping['source_field'];
            $targetField = $mapping['target_field'];

            if (isset($rawData[$sourceField])) {
                $mappedData[$targetField] = $rawData[$sourceField];
            }
        }

        // Update record with mapped data
        $record->update([
            'mapped_data' => json_encode($mappedData),
            'mapping_status' => 'auto_mapped',
        ]);
    }

    /**
     * Log mapping results
     */
    protected function logMappingResults(array $mappingResults, float $processingTime): void
    {
        $stats = [
            'total_fields' => count($mappingResults),
            'mapped_fields' => count(array_filter($mappingResults, fn ($r) => $r['mapped'])),
            'high_confidence' => count(array_filter($mappingResults, fn ($r) => $r['confidence'] >= $this->highConfidenceThreshold)),
            'low_confidence' => count(array_filter($mappingResults, fn ($r) => $r['confidence'] < $this->minConfidenceThreshold)),
            'avg_confidence' => count($mappingResults) > 0 ? array_sum(array_column($mappingResults, 'confidence')) / count($mappingResults) : 0,
        ];

        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => ImportLog::LOG_MAPPING_APPLIED,
            'severity' => ImportLog::SEVERITY_INFO,
            'message' => "Auto-mapping completed: {$stats['mapped_fields']}/{$stats['total_fields']} fields mapped",
            'detailed_message' => 'Field mapping completed in '.round($processingTime, 2).' seconds. Average confidence: '.round($stats['avg_confidence'], 2),
            'process_stage' => 'mapping',
            'processing_time' => $processingTime,
            'records_processed' => $stats['total_fields'],
            'final_data' => $stats,
        ]);
    }

    /**
     * Get appropriate temp model class based on import type
     */
    protected function getTempModelClass(): string
    {
        return match ($this->importJob->type) {
            ImportJob::TYPE_CUSTOMERS => ImportTempCustomer::class,
            ImportJob::TYPE_INVOICES => ImportTempInvoice::class,
            ImportJob::TYPE_ITEMS => ImportTempItem::class,
            ImportJob::TYPE_PAYMENTS => ImportTempPayment::class,
            ImportJob::TYPE_EXPENSES => ImportTempExpense::class,
            ImportJob::TYPE_COMPLETE => ImportTempCustomer::class,
            default => throw new \Exception("Unknown import type: {$this->importJob->type}"),
        };
    }

    /**
     * Handle job failure
     */
    protected function handleJobFailure(\Exception $exception): void
    {
        Log::error('Auto mapping job failed', [
            'import_job_id' => $this->importJob->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark import job as failed
        $this->importJob->markAsFailed(
            'Auto field mapping failed: '.$exception->getMessage(),
            [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stage' => 'mapping',
            ]
        );

        // Log failure
        ImportLog::logJobFailed($this->importJob, $exception->getMessage(), [
            'stage' => 'field_mapping',
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Handle job failure (Laravel queue method)
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleJobFailure($exception);
    }
}
