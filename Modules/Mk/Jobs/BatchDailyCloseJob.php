<?php

namespace Modules\Mk\Jobs;

use App\Models\DailyClosing;
use App\Services\PeriodLockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\BatchJob;

class BatchDailyCloseJob implements ShouldQueue
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
        $date = $params['date'] ?? now()->format('Y-m-d');
        $type = $params['type'] ?? DailyClosing::TYPE_ALL;
        $notes = $params['notes'] ?? 'Batch daily close';

        foreach ($this->batchJob->company_ids as $companyId) {
            try {
                // Check if already closed
                if (DailyClosing::isDateClosed($companyId, $date, $type)) {
                    $this->batchJob->incrementCompleted();
                    $this->batchJob->addResult($companyId, 'skipped', 'Date already closed');
                    continue;
                }

                $lockService->closeDay(
                    $companyId,
                    $date,
                    $type,
                    null,
                    $notes
                );

                $this->batchJob->incrementCompleted();
                $this->batchJob->addResult($companyId, 'success', 'Daily close completed');
            } catch (\Exception $e) {
                Log::warning('BatchDailyCloseJob failed for company', [
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
        Log::error('BatchDailyCloseJob failed entirely', [
            'batch_job_id' => $this->batchJob->id,
            'error' => $exception->getMessage(),
        ]);

        $this->batchJob->markFailed($exception->getMessage());
    }
}

// CLAUDE-CHECKPOINT
