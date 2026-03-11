<?php

namespace Modules\Mk\Jobs;

use App\Models\Company;
use App\Services\VatXmlService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Mk\Models\BatchJob;

class BatchVatReturnJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout in seconds (10 minutes).
     */
    public int $timeout = 600;

    /**
     * Maximum number of retries.
     */
    public int $tries = 1;

    public function __construct(public BatchJob $batchJob) {}

    public function handle(VatXmlService $vatService): void
    {
        $this->batchJob->markRunning();

        $params = $this->batchJob->parameters ?? [];
        $year = $params['year'] ?? now()->year;
        $month = $params['month'] ?? now()->month;

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $periodType = 'MONTHLY';

        foreach ($this->batchJob->company_ids as $companyId) {
            try {
                $company = Company::findOrFail($companyId);

                if (empty($company->vat_number)) {
                    $this->batchJob->incrementFailed();
                    $this->batchJob->addResult($companyId, 'failed', 'Company has no VAT number');
                    continue;
                }

                $xml = $vatService->generateVatReturn(
                    $company,
                    $periodStart,
                    $periodEnd,
                    $periodType
                );

                // Store the generated XML
                $companyName = preg_replace('/[^\p{L}\p{N}]+/u', '_', $company->name);
                $filename = sprintf(
                    'batch_vat/DDV04_%s_%s_%s.xml',
                    $companyName,
                    $periodStart->format('Y-m-d'),
                    $periodEnd->format('Y-m-d')
                );

                $stored = Storage::disk('local')->put($filename, $xml);
                if (!$stored) {
                    throw new \RuntimeException('Failed to write file to storage: ' . $filename);
                }

                $this->batchJob->incrementCompleted();
                $this->batchJob->addResult($companyId, 'success', 'VAT return generated', $filename);
            } catch (\Exception $e) {
                Log::warning('BatchVatReturnJob failed for company', [
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
        Log::error('BatchVatReturnJob failed entirely', [
            'batch_job_id' => $this->batchJob->id,
            'error' => $exception->getMessage(),
        ]);

        $this->batchJob->markFailed($exception->getMessage());
    }
}

// CLAUDE-CHECKPOINT
