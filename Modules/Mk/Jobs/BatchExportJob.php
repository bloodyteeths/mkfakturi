<?php

namespace Modules\Mk\Jobs;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Mk\Models\BatchJob;

class BatchExportJob implements ShouldQueue
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

    public function handle(IfrsAdapter $ifrsAdapter): void
    {
        $this->batchJob->markRunning();

        $params = $this->batchJob->parameters ?? [];
        $reportType = $params['report_type'] ?? 'trial_balance';
        $format = $params['format'] ?? 'csv';
        $dateFrom = $params['date_from'] ?? now()->startOfYear()->format('Y-m-d');
        $dateTo = $params['date_to'] ?? now()->format('Y-m-d');

        $generatedFiles = [];

        foreach ($this->batchJob->company_ids as $companyId) {
            try {
                $company = Company::findOrFail($companyId);

                $data = $this->generateReport(
                    $ifrsAdapter,
                    $company,
                    $reportType,
                    $dateFrom,
                    $dateTo
                );

                $companyName = preg_replace('/[^\p{L}\p{N}]+/u', '_', $company->name);
                $filename = sprintf(
                    'batch_exports/%s_%s_%s_%s.%s',
                    $reportType,
                    $companyName,
                    $dateFrom,
                    $dateTo,
                    $format
                );

                $content = $this->formatContent($data, $format, $reportType, $company->name);
                Storage::put($filename, $content);

                $generatedFiles[] = $filename;

                $this->batchJob->incrementCompleted();
                $this->batchJob->addResult($companyId, 'success', ucfirst(str_replace('_', ' ', $reportType)) . ' exported', $filename);
            } catch (\Exception $e) {
                Log::warning('BatchExportJob failed for company', [
                    'batch_job_id' => $this->batchJob->id,
                    'company_id' => $companyId,
                    'report_type' => $reportType,
                    'error' => $e->getMessage(),
                ]);

                $this->batchJob->incrementFailed();
                $this->batchJob->addResult($companyId, 'failed', $e->getMessage());
            }
        }

        $this->finalizeBatchJob();
    }

    /**
     * Generate a report for a company.
     */
    protected function generateReport(
        IfrsAdapter $ifrsAdapter,
        Company $company,
        string $reportType,
        string $dateFrom,
        string $dateTo
    ): array {
        return match ($reportType) {
            'trial_balance' => $this->extractRows(
                $ifrsAdapter->getTrialBalanceSixColumn($company, $dateFrom, $dateTo)
            ),
            'general_ledger', 'journal_entries' => $this->extractRows(
                $ifrsAdapter->getJournalEntries($company, $dateFrom, $dateTo)
            ),
            default => throw new \InvalidArgumentException("Unsupported report type: {$reportType}"),
        };
    }

    /**
     * Extract flat rows from adapter response (skip error/meta keys).
     */
    protected function extractRows(array $result): array
    {
        if (isset($result['error'])) {
            throw new \RuntimeException($result['error']);
        }

        // Trial balance returns ['accounts' => [...], 'totals' => [...]]
        if (isset($result['accounts'])) {
            return $result['accounts'];
        }

        // Journal entries returns ['entries' => [...]] or flat array
        if (isset($result['entries'])) {
            return $this->flattenJournalEntries($result['entries']);
        }

        return $result;
    }

    /**
     * Flatten journal entries (with nested line_items) into CSV-friendly rows.
     */
    protected function flattenJournalEntries(array $entries): array
    {
        $rows = [];
        foreach ($entries as $entry) {
            $lines = $entry['line_items'] ?? $entry['lines'] ?? [];
            foreach ($lines as $line) {
                $rows[] = [
                    'transaction_date' => $entry['transaction_date'] ?? $entry['date'] ?? '',
                    'transaction_no' => $entry['transaction_no'] ?? $entry['reference'] ?? '',
                    'narration' => $entry['narration'] ?? $entry['description'] ?? '',
                    'account_code' => $line['account_code'] ?? '',
                    'account_name' => $line['account_name'] ?? '',
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ];
            }
        }
        return $rows;
    }

    /**
     * Format the report data into the specified output format.
     */
    protected function formatContent(array $data, string $format, string $reportType, string $companyName): string
    {
        if ($format === 'csv') {
            return $this->toCsv($data, $reportType, $companyName);
        }

        // Default: JSON
        return json_encode([
            'company' => $companyName,
            'report_type' => $reportType,
            'data' => $data,
            'generated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Convert report data to CSV string.
     */
    protected function toCsv(array $data, string $reportType, string $companyName): string
    {
        $output = fopen('php://temp', 'r+');

        // Header comment
        fputcsv($output, ['# Company: ' . $companyName]);
        fputcsv($output, ['# Report: ' . ucfirst(str_replace('_', ' ', $reportType))]);
        fputcsv($output, ['# Generated: ' . now()->toDateTimeString()]);
        fputcsv($output, []);

        if (!empty($data)) {
            // Use first row keys as column headers
            $firstItem = reset($data);
            if (is_array($firstItem)) {
                fputcsv($output, array_keys($firstItem));
                foreach ($data as $row) {
                    if (is_array($row)) {
                        fputcsv($output, array_map(function ($value) {
                            return is_array($value) ? json_encode($value) : $value;
                        }, $row));
                    }
                }
            }
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
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
        Log::error('BatchExportJob failed entirely', [
            'batch_job_id' => $this->batchJob->id,
            'error' => $exception->getMessage(),
        ]);

        $this->batchJob->markFailed($exception->getMessage());
    }
}

// CLAUDE-CHECKPOINT
