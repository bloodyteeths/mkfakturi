<?php

namespace Modules\Mk\Jobs;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Mk\Models\BatchJob;

class BatchFinancialStatementExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(public BatchJob $batchJob) {}

    public function handle(): void
    {
        $ifrsAdapter = app(IfrsAdapter::class);

        $this->batchJob->markRunning();

        $params = $this->batchJob->parameters ?? [];
        $asOfDate = $params['as_of_date'] ?? now()->format('Y-m-d');
        $format = $params['format'] ?? 'csv';
        $opType = $this->batchJob->operation_type;

        foreach ($this->batchJob->company_ids as $companyId) {
            try {
                $company = Company::findOrFail($companyId);

                $data = match ($opType) {
                    'balance_sheet_export' => $ifrsAdapter->getBalanceSheet($company, $asOfDate),
                    'income_statement_export' => $ifrsAdapter->getIncomeStatement(
                        $company,
                        substr($asOfDate, 0, 4) . '-01-01',
                        $asOfDate
                    ),
                    default => throw new \InvalidArgumentException("Unsupported operation: {$opType}"),
                };

                if (isset($data['error'])) {
                    throw new \RuntimeException($data['error']);
                }

                $companyName = preg_replace('/[^\p{L}\p{N}]+/u', '_', $company->name);
                $reportLabel = $opType === 'balance_sheet_export' ? 'balance_sheet' : 'income_statement';
                $filename = sprintf(
                    'batch_exports/%s_%s_%s.%s',
                    $reportLabel,
                    $companyName,
                    $asOfDate,
                    $format
                );

                $content = $this->formatContent($data, $format, $reportLabel, $company->name);
                Storage::put($filename, $content);

                $this->batchJob->incrementCompleted();
                $this->batchJob->addResult($companyId, 'success', ucfirst(str_replace('_', ' ', $reportLabel)) . ' exported', $filename);
            } catch (\Exception $e) {
                Log::warning('BatchFinancialStatementExportJob failed for company', [
                    'batch_job_id' => $this->batchJob->id,
                    'company_id' => $companyId,
                    'operation' => $opType,
                    'error' => $e->getMessage(),
                ]);

                $this->batchJob->incrementFailed();
                $this->batchJob->addResult($companyId, 'failed', $e->getMessage());
            }
        }

        $this->finalizeBatchJob();
    }

    protected function formatContent(array $data, string $format, string $reportType, string $companyName): string
    {
        if ($format === 'csv') {
            return $this->toCsv($data, $reportType, $companyName);
        }

        return json_encode([
            'company' => $companyName,
            'report_type' => $reportType,
            'data' => $data,
            'generated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function toCsv(array $data, string $reportType, string $companyName): string
    {
        $output = fopen('php://temp', 'r+');

        fputcsv($output, ['# Company: ' . $companyName]);
        fputcsv($output, ['# Report: ' . ucfirst(str_replace('_', ' ', $reportType))]);
        fputcsv($output, ['# Generated: ' . now()->toDateTimeString()]);
        fputcsv($output, []);

        // Balance sheet: flatten sections into rows
        if ($reportType === 'balance_sheet') {
            fputcsv($output, ['Section', 'Account Code', 'Account Name', 'Amount']);
            foreach (['assets', 'liabilities', 'equity'] as $section) {
                $accounts = $data[$section]['accounts'] ?? $data[$section] ?? [];
                if (is_array($accounts)) {
                    foreach ($accounts as $account) {
                        if (is_array($account)) {
                            fputcsv($output, [
                                ucfirst($section),
                                $account['code'] ?? '',
                                $account['name'] ?? $account['account_name'] ?? '',
                                $account['balance'] ?? $account['amount'] ?? 0,
                            ]);
                        }
                    }
                }
            }
        }
        // Income statement: flatten into rows
        elseif ($reportType === 'income_statement') {
            fputcsv($output, ['Category', 'Account Code', 'Account Name', 'Amount']);
            foreach (['revenues', 'expenses', 'operating_revenue', 'operating_expenses', 'other_income', 'other_expenses'] as $category) {
                $items = $data[$category] ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if (is_array($item)) {
                            fputcsv($output, [
                                ucfirst(str_replace('_', ' ', $category)),
                                $item['code'] ?? '',
                                $item['name'] ?? $item['account_name'] ?? '',
                                $item['balance'] ?? $item['amount'] ?? 0,
                            ]);
                        }
                    }
                }
            }
            if (isset($data['net_income'])) {
                fputcsv($output, ['Net Income', '', '', $data['net_income']]);
            }
        }
        // Fallback: generic flat rows
        else {
            $firstItem = reset($data);
            if (is_array($firstItem)) {
                fputcsv($output, array_keys($firstItem));
                foreach ($data as $row) {
                    if (is_array($row)) {
                        fputcsv($output, array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $row));
                    }
                }
            }
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

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

    public function failed(\Throwable $exception): void
    {
        Log::error('BatchFinancialStatementExportJob failed entirely', [
            'batch_job_id' => $this->batchJob->id,
            'error' => $exception->getMessage(),
        ]);

        $this->batchJob->markFailed($exception->getMessage());
    }
}
