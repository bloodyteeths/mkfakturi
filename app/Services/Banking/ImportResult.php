<?php

namespace App\Services\Banking;

/**
 * P0-11: Import Result Value Object
 *
 * Immutable value object returned by DeduplicationService::importWithDedupe()
 * to report import statistics: how many were created, skipped, or failed.
 */
class ImportResult
{
    /**
     * @param  int  $created  Number of new transactions inserted
     * @param  int  $duplicates  Number of duplicate transactions skipped
     * @param  int  $failed  Number of transactions that failed to import
     * @param  array  $errors  Array of error messages for failed transactions
     * @param  array  $createdIds  Array of IDs for newly created transactions
     * @param  array  $duplicateFingerprints  Array of fingerprints that were duplicates
     */
    public function __construct(
        public readonly int $created = 0,
        public readonly int $duplicates = 0,
        public readonly int $failed = 0,
        public readonly array $errors = [],
        public readonly array $createdIds = [],
        public readonly array $duplicateFingerprints = [],
    ) {}

    /**
     * Get the total number of transactions processed.
     *
     * @return int Total processed (created + duplicates + failed)
     */
    public function total(): int
    {
        return $this->created + $this->duplicates + $this->failed;
    }

    /**
     * Check if the import had any failures.
     *
     * @return bool True if any transactions failed to import
     */
    public function hasErrors(): bool
    {
        return $this->failed > 0;
    }

    /**
     * Check if the import was completely successful (no failures).
     *
     * @return bool True if no transactions failed
     */
    public function isClean(): bool
    {
        return $this->failed === 0;
    }

    /**
     * Get a summary string for logging/display.
     *
     * @return string Human-readable summary
     */
    public function summary(): string
    {
        return sprintf(
            'Import complete: %d created, %d duplicates skipped, %d failed (total: %d)',
            $this->created,
            $this->duplicates,
            $this->failed,
            $this->total()
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'duplicates' => $this->duplicates,
            'failed' => $this->failed,
            'total' => $this->total(),
            'errors' => $this->errors,
            'created_ids' => $this->createdIds,
            'duplicate_fingerprints' => $this->duplicateFingerprints,
        ];
    }
}

// CLAUDE-CHECKPOINT
