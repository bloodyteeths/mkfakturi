<?php

namespace App\Services\Banking;

use App\Models\BankImportLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * P0-03: Import Logging Service
 *
 * Tracks bank CSV import operations with per-import statistics.
 * Provides start/complete/fail lifecycle methods and aggregate
 * analytics (success rate, parse time, per-bank breakdown).
 */
class ImportLoggingService
{
    /**
     * Start tracking an import operation (call at beginning of import).
     *
     * Creates a BankImportLog record in 'pending' status.
     *
     * @param  int  $companyId  The company performing the import
     * @param  int  $userId  The user performing the import
     * @param  string  $bankCode  The bank code (e.g. 'nlb', 'stopanska')
     * @param  string  $fileName  Original uploaded file name
     * @param  int  $fileSizeBytes  File size in bytes
     * @return BankImportLog The created log record
     */
    public function startImport(
        int $companyId,
        int $userId,
        string $bankCode,
        string $fileName,
        int $fileSizeBytes
    ): BankImportLog {
        $log = BankImportLog::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'bank_code' => $bankCode,
            'file_name' => $fileName,
            'file_size_bytes' => $fileSizeBytes,
            'status' => BankImportLog::STATUS_PENDING,
        ]);

        Log::info('P0-03: Import started', [
            'import_log_id' => $log->id,
            'company_id' => $companyId,
            'bank_code' => $bankCode,
            'file_name' => $fileName,
        ]);

        return $log;
    }

    /**
     * Complete the import log with results.
     *
     * Updates the log with final row counts and determines status
     * (completed, partial, or failed) based on the results.
     *
     * @param  BankImportLog  $log  The import log to complete
     * @param  int  $totalRows  Total rows in the CSV
     * @param  int  $parsedRows  Rows successfully parsed
     * @param  int  $importedRows  Rows imported (new transactions)
     * @param  int  $duplicateRows  Rows skipped as duplicates
     * @param  int  $failedRows  Rows that failed to import
     * @param  array|null  $errors  Array of error messages
     * @param  int  $parseTimeMs  Time to parse/import in milliseconds
     * @return BankImportLog The updated log record
     */
    public function completeImport(
        BankImportLog $log,
        int $totalRows,
        int $parsedRows,
        int $importedRows,
        int $duplicateRows,
        int $failedRows,
        ?array $errors,
        int $parseTimeMs
    ): BankImportLog {
        $log->total_rows = $totalRows;
        $log->parsed_rows = $parsedRows;
        $log->imported_rows = $importedRows;
        $log->duplicate_rows = $duplicateRows;
        $log->failed_rows = $failedRows;
        $log->errors = $errors;
        $log->parse_time_ms = $parseTimeMs;
        $log->status = $log->computeStatus();
        $log->save();

        Log::info('P0-03: Import completed', [
            'import_log_id' => $log->id,
            'status' => $log->status,
            'imported' => $importedRows,
            'duplicates' => $duplicateRows,
            'failed' => $failedRows,
            'parse_time_ms' => $parseTimeMs,
        ]);

        return $log;
    }

    /**
     * Mark import as failed.
     *
     * Sets the log status to 'failed' with the error message.
     *
     * @param  BankImportLog  $log  The import log to mark as failed
     * @param  string  $error  The error message
     * @param  int  $parseTimeMs  Time elapsed before failure in milliseconds
     * @return BankImportLog The updated log record
     */
    public function failImport(
        BankImportLog $log,
        string $error,
        int $parseTimeMs
    ): BankImportLog {
        $log->status = BankImportLog::STATUS_FAILED;
        $log->errors = [$error];
        $log->parse_time_ms = $parseTimeMs;
        $log->save();

        Log::warning('P0-03: Import failed', [
            'import_log_id' => $log->id,
            'error' => $error,
            'parse_time_ms' => $parseTimeMs,
        ]);

        return $log;
    }

    /**
     * Get import statistics for a company.
     *
     * Returns aggregate metrics: total imports, success rate,
     * average parse time, per-bank breakdown, and common errors.
     *
     * @param  int  $companyId  The company to get stats for
     * @param  string|null  $fromDate  Start date (Y-m-d), defaults to 30 days ago
     * @param  string|null  $toDate  End date (Y-m-d), defaults to today
     * @return array Aggregated import statistics
     */
    public function getStats(
        int $companyId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $from = $fromDate
            ? Carbon::parse($fromDate)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $to = $toDate
            ? Carbon::parse($toDate)->endOfDay()
            : Carbon::now()->endOfDay();

        $query = BankImportLog::forCompany($companyId)
            ->whereBetween('created_at', [$from, $to]);

        // Overall stats
        $totalImports = (clone $query)->count();
        $successfulImports = (clone $query)->successful()->count();
        $failedImports = (clone $query)->failed()->count();

        $avgParseTime = (clone $query)->avg('parse_time_ms');
        $totalRowsImported = (clone $query)->sum('imported_rows');
        $totalDuplicates = (clone $query)->sum('duplicate_rows');
        $totalFailed = (clone $query)->sum('failed_rows');

        // Per-bank breakdown
        $perBank = (clone $query)
            ->select(
                'bank_code',
                DB::raw('COUNT(*) as import_count'),
                DB::raw('SUM(imported_rows) as total_imported'),
                DB::raw('SUM(duplicate_rows) as total_duplicates'),
                DB::raw('SUM(failed_rows) as total_failed'),
                DB::raw('AVG(parse_time_ms) as avg_parse_time_ms'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success_count')
            )
            ->groupBy('bank_code')
            ->get()
            ->map(function ($row) {
                return [
                    'bank_code' => $row->bank_code,
                    'import_count' => (int) $row->import_count,
                    'total_imported' => (int) $row->total_imported,
                    'total_duplicates' => (int) $row->total_duplicates,
                    'total_failed' => (int) $row->total_failed,
                    'avg_parse_time_ms' => round((float) $row->avg_parse_time_ms),
                    'success_rate' => $row->import_count > 0
                        ? round(($row->success_count / $row->import_count) * 100, 1)
                        : 0,
                ];
            })
            ->toArray();

        // Error frequency (top errors from failed imports)
        $errorFrequency = $this->getErrorFrequency($companyId, $from, $to);

        return [
            'total_imports' => $totalImports,
            'successful_imports' => $successfulImports,
            'failed_imports' => $failedImports,
            'success_rate' => $totalImports > 0
                ? round(($successfulImports / $totalImports) * 100, 1)
                : 0,
            'avg_parse_time_ms' => round((float) $avgParseTime),
            'total_rows_imported' => (int) $totalRowsImported,
            'total_duplicates' => (int) $totalDuplicates,
            'total_failed_rows' => (int) $totalFailed,
            'per_bank' => $perBank,
            'error_frequency' => $errorFrequency,
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }

    /**
     * Get frequency of common error messages for a company.
     *
     * @param  int  $companyId  The company to analyze
     * @param  Carbon  $from  Start date
     * @param  Carbon  $to  End date
     * @return array Top error messages with counts
     */
    protected function getErrorFrequency(int $companyId, Carbon $from, Carbon $to): array
    {
        $logsWithErrors = BankImportLog::forCompany($companyId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('errors')
            ->pluck('errors');

        $errorCounts = [];
        foreach ($logsWithErrors as $errors) {
            if (is_array($errors)) {
                foreach ($errors as $error) {
                    $key = mb_substr((string) $error, 0, 100);
                    $errorCounts[$key] = ($errorCounts[$key] ?? 0) + 1;
                }
            }
        }

        arsort($errorCounts);

        // Return top 10
        return array_slice(
            array_map(
                fn ($msg, $count) => ['message' => $msg, 'count' => $count],
                array_keys($errorCounts),
                array_values($errorCounts)
            ),
            0,
            10
        );
    }
}

// CLAUDE-CHECKPOINT
