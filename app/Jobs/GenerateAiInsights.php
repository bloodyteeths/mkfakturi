<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\AiInsightsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate AI Insights Job
 *
 * Background job for generating AI-powered financial insights.
 * This job is queued to avoid blocking HTTP requests during analysis.
 */
class GenerateAiInsights implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param Company $company The company to analyze
     */
    public function __construct(
        public Company $company
    ) {}

    /**
     * Execute the job.
     *
     * @param AiInsightsService $aiService
     * @return void
     */
    public function handle(AiInsightsService $aiService): void
    {
        try {
            Log::info('Starting AI insights generation', [
                'company_id' => $this->company->id,
                'company_name' => $this->company->name,
                'attempt' => $this->attempts(),
            ]);

            // Generate insights
            $insights = $aiService->analyzeFinancials($this->company);

            Log::info('AI insights generated successfully', [
                'company_id' => $this->company->id,
                'insights_count' => count($insights['items'] ?? []),
                'provider' => $insights['provider'] ?? null,
                'model' => $insights['model'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('AI insights generation failed', [
                'company_id' => $this->company->id,
                'company_name' => $this->company->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // If we've exhausted all attempts, don't retry
            if ($this->attempts() >= $this->tries) {
                Log::error('AI insights generation failed after all attempts', [
                    'company_id' => $this->company->id,
                    'attempts' => $this->attempts(),
                ]);
            }

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AI insights job permanently failed', [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // TODO: Optionally notify admin or company owner
        // Could dispatch a notification here
    }

    /**
     * Get the tags for the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'ai-insights',
            'company:' . $this->company->id,
        ];
    }
}
