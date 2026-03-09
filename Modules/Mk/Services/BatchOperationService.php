<?php

namespace Modules\Mk\Services;

use App\Models\Partner;
use Modules\Mk\Jobs\BatchDailyCloseJob;
use Modules\Mk\Jobs\BatchExportJob;
use Modules\Mk\Jobs\BatchFinancialStatementExportJob;
use Modules\Mk\Jobs\BatchPeriodLockJob;
use Modules\Mk\Jobs\BatchVatReturnJob;
use Modules\Mk\Models\BatchJob;

class BatchOperationService
{
    /**
     * Valid operation types and their corresponding job classes.
     */
    protected array $operationJobs = [
        'daily_close' => BatchDailyCloseJob::class,
        'vat_return' => BatchVatReturnJob::class,
        'trial_balance_export' => BatchExportJob::class,
        'period_lock' => BatchPeriodLockJob::class,
        'journal_export' => BatchExportJob::class,
        'balance_sheet_export' => BatchFinancialStatementExportJob::class,
        'income_statement_export' => BatchFinancialStatementExportJob::class,
    ];

    /**
     * Create a new batch job and dispatch it.
     * Accepts Partner object or partner ID. For super admin, pass the fake Partner object.
     */
    public function createJob(int|Partner $partnerOrId, string $operationType, array $companyIds, array $parameters = []): BatchJob
    {
        if (!array_key_exists($operationType, $this->operationJobs)) {
            throw new \InvalidArgumentException("Invalid operation type: {$operationType}");
        }

        // Resolve partner
        if ($partnerOrId instanceof Partner) {
            $partner = $partnerOrId;
        } else {
            $partner = Partner::findOrFail($partnerOrId);
        }

        $this->validateCompanyAccess($partner, $companyIds);

        // For super admin (fake partner id=0), store null partner_id
        $storedPartnerId = ($partner->id > 0) ? $partner->id : null;

        // Create the batch job record
        $batchJob = BatchJob::create([
            'partner_id' => $storedPartnerId,
            'operation_type' => $operationType,
            'company_ids' => $companyIds,
            'parameters' => $parameters,
            'status' => 'queued',
            'total_items' => count($companyIds),
            'completed_items' => 0,
            'failed_items' => 0,
            'results' => [],
        ]);

        // Dispatch the appropriate job to the queue
        $jobClass = $this->operationJobs[$operationType];
        dispatch(new $jobClass($batchJob))->onQueue('background');

        return $batchJob;
    }

    /**
     * List batch jobs for a partner with optional filters.
     * Partner ID 0 = super admin — sees all jobs.
     */
    public function getJobs(int $partnerId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = BatchJob::orderBy('created_at', 'desc');

        // Super admin sees all jobs; regular partners only see their own
        if ($partnerId > 0) {
            $query->forPartner($partnerId);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['operation_type'])) {
            $query->byType($filters['operation_type']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get a single batch job with results.
     */
    public function getJob(int $partnerId, int $jobId): BatchJob
    {
        $query = BatchJob::query();
        if ($partnerId > 0) {
            $query->forPartner($partnerId);
        }
        return $query->findOrFail($jobId);
    }

    /**
     * Cancel a queued batch job.
     */
    public function cancelJob(int $partnerId, int $jobId): BatchJob
    {
        $query = BatchJob::query();
        if ($partnerId > 0) {
            $query->forPartner($partnerId);
        }
        $batchJob = $query->findOrFail($jobId);

        if ($batchJob->status !== 'queued') {
            throw new \RuntimeException('Only queued jobs can be cancelled.');
        }

        $batchJob->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_log' => 'Cancelled by user.',
        ]);

        return $batchJob->fresh();
    }

    /**
     * Get the list of available operation types with labels.
     */
    public function getAvailableOperations(): array
    {
        return [
            [
                'key' => 'daily_close',
                'label' => 'Daily Close',
                'description' => 'Close the day for selected companies.',
                'icon' => 'CalendarIcon',
                'requires_params' => ['date'],
            ],
            [
                'key' => 'vat_return',
                'label' => 'VAT Return',
                'description' => 'Generate DDV-04 XML for selected companies.',
                'icon' => 'DocumentTextIcon',
                'requires_params' => ['year', 'month'],
            ],
            [
                'key' => 'trial_balance_export',
                'label' => 'Trial Balance Export',
                'description' => 'Export trial balance reports for selected companies.',
                'icon' => 'ArrowDownTrayIcon',
                'requires_params' => ['date_from', 'date_to', 'format'],
            ],
            [
                'key' => 'period_lock',
                'label' => 'Period Lock',
                'description' => 'Lock a period for selected companies.',
                'icon' => 'LockClosedIcon',
                'requires_params' => ['period_start', 'period_end'],
            ],
            [
                'key' => 'journal_export',
                'label' => 'Journal Export',
                'description' => 'Export journal entries for selected companies.',
                'icon' => 'ArrowDownTrayIcon',
                'requires_params' => ['date_from', 'date_to', 'format'],
            ],
            [
                'key' => 'balance_sheet_export',
                'label' => 'Balance Sheet Export',
                'description' => 'Export balance sheet for selected companies.',
                'icon' => 'ScaleIcon',
                'requires_params' => ['as_of_date', 'format'],
            ],
            [
                'key' => 'income_statement_export',
                'label' => 'Income Statement Export',
                'description' => 'Export income statement for selected companies.',
                'icon' => 'ChartBarIcon',
                'requires_params' => ['as_of_date', 'format'],
            ],
        ];
    }

    /**
     * Validate that the partner has access to all specified companies.
     */
    protected function validateCompanyAccess(Partner $partner, array $companyIds): void
    {
        // Super admin has access to all
        if ($partner->is_super_admin ?? false) {
            return;
        }

        $accessibleCompanyIds = $partner->companies()
            ->where('partner_company_links.is_active', true)
            ->pluck('companies.id')
            ->toArray();

        $inaccessible = array_diff($companyIds, $accessibleCompanyIds);

        if (!empty($inaccessible)) {
            throw new \InvalidArgumentException(
                'Partner does not have access to companies: ' . implode(', ', $inaccessible)
            );
        }
    }
}

// CLAUDE-CHECKPOINT
