<?php

namespace Modules\Mk\Jobs;

use App\Models\PeriodLock;
use App\Services\PeriodLockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\BatchJob;

class BatchPeriodLockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout in seconds (5 minutes).
     */
    public int $timeout = 300;

    /**
     * Maximum number of retries.
     */
    public int $tries = 1;

    public function __construct(public BatchJob $batchJob) {}

    public function handle(PeriodLockService $lockService): void
    {
        $this->batchJob->markRunning();

        $params = $this->batchJob->parameters ?? [];
        $periodStart = $params['period_start'] ?? now()->startOfMonth()->format('Y-m-d');
        $periodEnd = $params['period_end'] ?? now()->endOfMonth()->format('Y-m-d');
        $notes = $params['notes'] ?? 'Batch period lock';

        foreach ($this->batchJob->company_ids as $companyId) {
            try {
                // Check for overlapping locks
                $overlapping = PeriodLock::getOverlappingLocks(
                    $companyId,
                    $periodStart,
                    $periodEnd
                );

                if ($overlapping->isNotEmpty()) {
                    $this->batchJob->incrementCompleted();
                    $this->batchJob->addResult($companyId, 'skipped', 'Period already locked');
                    continue;
                }

                $lockService->lockPeriod(
                    $companyId,
                    $periodStart,
                    $periodEnd,
                    null,
                    $notes
                );

                $this->batchJob->incrementCompleted();
                $this->batchJob->addResult($companyId, 'success', 'Period locked successfully');
            } catch (\Exception $e) {
                Log::warning('BatchPeriodLockJob failed for company', [
                    'batch_job_id' => $this->batchJob->id,
                    'company_id' => $companyId,
                    'error' => $e->getMessage(),
                ]);

                $this->batchJob->incrementFailed();
                $this->batchJob->addResult($companyId, 'failed', $e->getMessage());
            }
        }

        $this->finalizeBatchJob();
    }

    /**
     * Determine final status of the batch job.
     */
    protected function finalizeBatchJob(): void
    {
        $this->batchJob->refresh();

        if ($this->batchJob->failed_items > 0 && $this->batchJob->completed_items > 0) {
            $this->batchJob->update([
                'status' => 'partially_failed',
                'completed_at' => now(),
            ]);
        } elseif ($this->batchJob->failed_items > 0 && $this->batchJob->completed_items === 0) {
            $this->batchJob->markFailed('All companies failed');
        } else {
            $this->batchJob->markCompleted();
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BatchPeriodLockJob failed entirely', [
            'batch_job_id' => $this->batchJob->id,
            'error' => $exception->getMessage(),
        ]);

        $this->batchJob->markFailed($exception->getMessage());
    }
}

// CLAUDE-CHECKPOINT
