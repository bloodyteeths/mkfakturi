<?php

namespace App\Jobs;

use App\Models\TaxReportPeriod;
use App\Models\TaxReturn;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Backfill Tax Returns Job
 *
 * Migrates historical DDV (VAT) XML files from storage to the tax returns system.
 * Scans for existing DDV XML files and creates corresponding TaxReportPeriod
 * and TaxReturn records.
 *
 * Logic:
 * - Scans storage paths for DDV XML files (from old manual process)
 * - Parses XML to extract period information (year, month/quarter)
 * - Creates TaxReportPeriod if it doesn't exist
 * - Creates TaxReturn marked as FILED with receipt number if available
 * - Idempotent: skips if periods/returns already exist
 * - Logs what was backfilled for audit trail
 *
 * Storage paths checked:
 * - storage/app/tax/ddv/*.xml
 * - storage/app/tax/vat/*.xml
 * - storage/app/company/{id}/tax/*.xml
 *
 * Queue: default
 */
class BackfillTaxReturnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The company ID to backfill (null = all companies)
     *
     * @var int|null
     */
    protected ?int $companyId;

    /**
     * Dry run mode (log only, don't create records)
     *
     * @var bool
     */
    protected bool $dryRun;

    /**
     * Storage paths to scan for DDV XML files
     *
     * @var array
     */
    protected array $scanPaths = [
        'tax/ddv',
        'tax/vat',
        'tax/returns',
    ];

    /**
     * Job timeout in seconds
     *
     * @var int
     */
    public $timeout = 1800; // 30 minutes

    /**
     * Create a new job instance.
     *
     * @param int|null $companyId Company to backfill (null = all companies)
     * @param bool $dryRun If true, only log what would be done
     */
    public function __construct(?int $companyId = null, bool $dryRun = false)
    {
        $this->companyId = $companyId;
        $this->dryRun = $dryRun;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $mode = $this->dryRun ? '[DRY RUN]' : '';

        Log::info("$mode Starting tax returns backfill", [
            'company_id' => $this->companyId,
            'dry_run' => $this->dryRun,
        ]);

        $totalFiles = 0;
        $totalPeriods = 0;
        $totalReturns = 0;
        $totalSkipped = 0;
        $errors = [];

        // Scan each storage path
        foreach ($this->scanPaths as $path) {
            try {
                $result = $this->scanPath($path);

                $totalFiles += $result['files'];
                $totalPeriods += $result['periods'];
                $totalReturns += $result['returns'];
                $totalSkipped += $result['skipped'];

                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }
            } catch (\Exception $e) {
                Log::error("$mode Failed to scan path", [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);

                $errors[] = [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Also scan company-specific paths if processing single company
        if ($this->companyId !== null) {
            $companyPath = "company/{$this->companyId}/tax";
            try {
                if (Storage::exists($companyPath)) {
                    $result = $this->scanPath($companyPath);

                    $totalFiles += $result['files'];
                    $totalPeriods += $result['periods'];
                    $totalReturns += $result['returns'];
                    $totalSkipped += $result['skipped'];

                    if (!empty($result['errors'])) {
                        $errors = array_merge($errors, $result['errors']);
                    }
                }
            } catch (\Exception $e) {
                Log::error("$mode Failed to scan company path", [
                    'path' => $companyPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("$mode Tax returns backfill completed", [
            'total_files' => $totalFiles,
            'total_periods' => $totalPeriods,
            'total_returns' => $totalReturns,
            'total_skipped' => $totalSkipped,
            'errors' => count($errors),
            'error_details' => array_slice($errors, 0, 10), // Limit error log size
        ]);
    }

    /**
     * Scan a storage path for DDV XML files
     *
     * @param string $path Storage path to scan
     * @return array Result statistics
     */
    protected function scanPath(string $path): array
    {
        $mode = $this->dryRun ? '[DRY RUN]' : '';

        Log::info("$mode Scanning path for DDV XML files", ['path' => $path]);

        $filesFound = 0;
        $periodsCreated = 0;
        $returnsCreated = 0;
        $skipped = 0;
        $errors = [];

        // Check if path exists
        if (!Storage::exists($path)) {
            Log::info("$mode Path does not exist, skipping", ['path' => $path]);

            return [
                'files' => 0,
                'periods' => 0,
                'returns' => 0,
                'skipped' => 0,
                'errors' => [],
            ];
        }

        // Get all XML files in the path
        $files = Storage::files($path);
        $xmlFiles = array_filter($files, fn ($file) => str_ends_with(strtolower($file), '.xml'));

        Log::info("$mode Found XML files", [
            'path' => $path,
            'count' => count($xmlFiles),
        ]);

        foreach ($xmlFiles as $file) {
            try {
                $filesFound++;

                $result = $this->processXmlFile($file);

                if ($result['created']) {
                    if ($result['period_created']) {
                        $periodsCreated++;
                    }
                    $returnsCreated++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'file' => $file,
                    'error' => $e->getMessage(),
                ];

                Log::error("$mode Failed to process XML file", [
                    'file' => $file,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'files' => $filesFound,
            'periods' => $periodsCreated,
            'returns' => $returnsCreated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Process a single DDV XML file
     *
     * @param string $filePath Storage file path
     * @return array Result with 'created' and 'period_created' flags
     */
    protected function processXmlFile(string $filePath): array
    {
        $mode = $this->dryRun ? '[DRY RUN]' : '';

        // Read and parse XML
        $xmlContent = Storage::get($filePath);
        $data = $this->parseXml($xmlContent, $filePath);

        if ($data === null) {
            Log::warning("$mode Could not parse XML file, skipping", ['file' => $filePath]);

            return ['created' => false, 'period_created' => false];
        }

        // Filter by company if specified
        if ($this->companyId !== null && $data['company_id'] !== $this->companyId) {
            Log::debug("$mode XML file is for different company, skipping", [
                'file' => $filePath,
                'file_company_id' => $data['company_id'],
                'filter_company_id' => $this->companyId,
            ]);

            return ['created' => false, 'period_created' => false];
        }

        // Check if return already exists for this period (idempotent)
        $existingReturn = TaxReturn::where('company_id', $data['company_id'])
            ->whereHas('period', function ($query) use ($data) {
                $query->where('year', $data['year'])
                    ->where('period_type', $data['period_type']);

                if ($data['month']) {
                    $query->where('month', $data['month']);
                }
                if ($data['quarter']) {
                    $query->where('quarter', $data['quarter']);
                }
            })
            ->where('return_type', TaxReturn::TYPE_VAT)
            ->whereIn('status', [TaxReturn::STATUS_FILED, TaxReturn::STATUS_ACCEPTED])
            ->first();

        if ($existingReturn) {
            Log::debug("$mode Tax return already exists for this period, skipping", [
                'file' => $filePath,
                'company_id' => $data['company_id'],
                'period' => $data['period_name'],
                'return_id' => $existingReturn->id,
            ]);

            return ['created' => false, 'period_created' => false];
        }

        if ($this->dryRun) {
            Log::info("$mode Would create tax return from XML", [
                'file' => $filePath,
                'company_id' => $data['company_id'],
                'period_type' => $data['period_type'],
                'year' => $data['year'],
                'month' => $data['month'],
                'quarter' => $data['quarter'],
                'period_name' => $data['period_name'],
                'receipt_number' => $data['receipt_number'],
            ]);

            return ['created' => false, 'period_created' => false];
        }

        // Create period and return in a transaction
        $periodCreated = false;

        DB::transaction(function () use ($data, $filePath, &$periodCreated) {
            // Create or get tax report period
            $period = $this->getOrCreatePeriod($data);

            if ($period->wasRecentlyCreated) {
                $periodCreated = true;
            }

            // Create tax return
            $taxReturn = TaxReturn::create([
                'company_id' => $data['company_id'],
                'period_id' => $period->id,
                'return_type' => TaxReturn::TYPE_VAT,
                'status' => TaxReturn::STATUS_FILED,
                'return_data' => [
                    'backfill' => true,
                    'source_file' => $filePath,
                    'imported_at' => now()->toDateTimeString(),
                    'xml_data' => $data['xml_summary'],
                ],
                'response_data' => null,
                'submission_reference' => $data['receipt_number'],
                'submitted_at' => $data['submitted_at'] ?? $period->end_date,
                'submitted_by_id' => null, // Unknown who submitted historically
                'accepted_at' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'amendment_of_id' => null,
            ]);

            Log::info('Created tax return from backfilled XML', [
                'return_id' => $taxReturn->id,
                'period_id' => $period->id,
                'company_id' => $data['company_id'],
                'period_name' => $data['period_name'],
                'file' => $filePath,
                'receipt_number' => $data['receipt_number'],
            ]);
        });

        return ['created' => true, 'period_created' => $periodCreated];
    }

    /**
     * Parse DDV XML file to extract period and submission data
     *
     * @param string $xmlContent XML file content
     * @param string $filePath File path for logging
     * @return array|null Parsed data or null if failed
     */
    protected function parseXml(string $xmlContent, string $filePath): ?array
    {
        try {
            // Suppress XML errors and use internal error handling
            libxml_use_internal_errors(true);

            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();

                Log::warning('Failed to parse XML', [
                    'file' => $filePath,
                    'errors' => array_map(fn ($e) => $e->message, $errors),
                ]);

                return null;
            }

            // Try to extract period information from XML
            // This is a best-effort parser for common DDV XML formats
            $year = null;
            $month = null;
            $quarter = null;
            $companyId = null;
            $receiptNumber = null;
            $submittedAt = null;

            // Extract year (various possible locations)
            if (isset($xml->Year)) {
                $year = (int) $xml->Year;
            } elseif (isset($xml->TaxPeriod->Year)) {
                $year = (int) $xml->TaxPeriod->Year;
            } elseif (isset($xml->Header->Year)) {
                $year = (int) $xml->Header->Year;
            }

            // Extract month/quarter
            if (isset($xml->Month)) {
                $month = (int) $xml->Month;
            } elseif (isset($xml->TaxPeriod->Month)) {
                $month = (int) $xml->TaxPeriod->Month;
            } elseif (isset($xml->Quarter)) {
                $quarter = (int) $xml->Quarter;
            } elseif (isset($xml->TaxPeriod->Quarter)) {
                $quarter = (int) $xml->TaxPeriod->Quarter;
            }

            // Extract company ID from filename or XML
            // Filename pattern: company_123_ddv_2024_03.xml
            if (preg_match('/company[_-](\d+)/i', $filePath, $matches)) {
                $companyId = (int) $matches[1];
            } elseif (isset($xml->CompanyId)) {
                $companyId = (int) $xml->CompanyId;
            } elseif (isset($xml->Header->CompanyId)) {
                $companyId = (int) $xml->Header->CompanyId;
            }

            // Extract receipt number if available
            if (isset($xml->ReceiptNumber)) {
                $receiptNumber = (string) $xml->ReceiptNumber;
            } elseif (isset($xml->SubmissionReceipt)) {
                $receiptNumber = (string) $xml->SubmissionReceipt;
            } elseif (isset($xml->Header->ReceiptNumber)) {
                $receiptNumber = (string) $xml->Header->ReceiptNumber;
            }

            // Extract submission date if available
            if (isset($xml->SubmittedAt)) {
                $submittedAt = Carbon::parse((string) $xml->SubmittedAt);
            } elseif (isset($xml->SubmissionDate)) {
                $submittedAt = Carbon::parse((string) $xml->SubmissionDate);
            }

            // Fallback: try to extract from filename
            // Pattern: ddv_2024_03.xml or vat_2024_q1.xml
            if (!$year || (!$month && !$quarter)) {
                if (preg_match('/(\d{4})[_-](\d{1,2})/', basename($filePath), $matches)) {
                    $year = $year ?? (int) $matches[1];
                    $month = $month ?? (int) $matches[2];
                } elseif (preg_match('/(\d{4})[_-]q(\d)/', basename($filePath), $matches)) {
                    $year = $year ?? (int) $matches[1];
                    $quarter = $quarter ?? (int) $matches[2];
                }
            }

            // Validation: we need at least year and (month or quarter) and company_id
            if (!$year || (!$month && !$quarter) || !$companyId) {
                Log::warning('XML missing required period information', [
                    'file' => $filePath,
                    'year' => $year,
                    'month' => $month,
                    'quarter' => $quarter,
                    'company_id' => $companyId,
                ]);

                return null;
            }

            // Determine period type
            $periodType = $month ? TaxReportPeriod::PERIOD_MONTHLY : TaxReportPeriod::PERIOD_QUARTERLY;

            // Calculate start and end dates
            if ($month) {
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                $periodName = $startDate->format('F Y');
            } else {
                $startMonth = ($quarter - 1) * 3 + 1;
                $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfMonth();
                $endDate = $startDate->copy()->addMonths(2)->endOfMonth();
                $periodName = "Q{$quarter} {$year}";
            }

            // Create XML summary for return_data
            $xmlSummary = [
                'filename' => basename($filePath),
                'file_size' => strlen($xmlContent),
                'parsed_at' => now()->toDateTimeString(),
            ];

            // Try to extract some basic tax data if available
            if (isset($xml->TotalVAT)) {
                $xmlSummary['total_vat'] = (float) $xml->TotalVAT;
            }
            if (isset($xml->TaxableBase)) {
                $xmlSummary['taxable_base'] = (float) $xml->TaxableBase;
            }

            return [
                'company_id' => $companyId,
                'year' => $year,
                'month' => $month,
                'quarter' => $quarter,
                'period_type' => $periodType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'period_name' => $periodName,
                'receipt_number' => $receiptNumber,
                'submitted_at' => $submittedAt,
                'xml_summary' => $xmlSummary,
            ];
        } catch (\Exception $e) {
            Log::error('Exception while parsing XML', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get or create tax report period
     *
     * @param array $data Period data from parsed XML
     * @return TaxReportPeriod
     */
    protected function getOrCreatePeriod(array $data): TaxReportPeriod
    {
        // Check if period already exists
        $period = TaxReportPeriod::where('company_id', $data['company_id'])
            ->where('year', $data['year'])
            ->where('period_type', $data['period_type'])
            ->when($data['month'], fn ($q) => $q->where('month', $data['month']))
            ->when($data['quarter'], fn ($q) => $q->where('quarter', $data['quarter']))
            ->first();

        if ($period) {
            Log::debug('Using existing tax report period', [
                'period_id' => $period->id,
                'company_id' => $data['company_id'],
                'period_name' => $data['period_name'],
            ]);

            return $period;
        }

        // Create new period
        $period = TaxReportPeriod::create([
            'company_id' => $data['company_id'],
            'period_type' => $data['period_type'],
            'year' => $data['year'],
            'month' => $data['month'],
            'quarter' => $data['quarter'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => TaxReportPeriod::STATUS_FILED,
            'closed_at' => $data['submitted_at'] ?? $data['end_date'],
            'closed_by_id' => null, // Unknown who closed historically
            'reopened_at' => null,
            'reopened_by_id' => null,
            'reopen_reason' => null,
        ]);

        Log::info('Created tax report period from backfill', [
            'period_id' => $period->id,
            'company_id' => $data['company_id'],
            'period_name' => $data['period_name'],
        ]);

        return $period;
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Tax returns backfill job failed', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

// CLAUDE-CHECKPOINT
