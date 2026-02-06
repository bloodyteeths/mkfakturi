<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * P0-03: Bank Import Log Model
 *
 * Tracks each bank CSV import operation with statistics:
 * total/parsed/imported/duplicate/failed row counts, errors,
 * parse time, and final status (completed/failed/partial).
 *
 * Used by ImportLoggingService and the import-history API.
 * Separate from ImportLog (Migration Wizard) to avoid model collision.
 */
class BankImportLog extends Model
{
    use BelongsToCompany;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'import_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'bank_code',
        'file_name',
        'file_size_bytes',
        'total_rows',
        'parsed_rows',
        'imported_rows',
        'duplicate_rows',
        'failed_rows',
        'errors',
        'parse_time_ms',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'errors' => 'array',
        'file_size_bytes' => 'integer',
        'total_rows' => 'integer',
        'parsed_rows' => 'integer',
        'imported_rows' => 'integer',
        'duplicate_rows' => 'integer',
        'failed_rows' => 'integer',
        'parse_time_ms' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_PARTIAL = 'partial';

    /**
     * Get the company that owns this import log.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who performed the import.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by bank code.
     *
     * @param  Builder  $query
     * @param  string  $bankCode
     * @return Builder
     */
    public function scopeForBank(Builder $query, string $bankCode): Builder
    {
        return $query->where('bank_code', $bankCode);
    }

    /**
     * Scope: Filter to recent imports within N days.
     *
     * @param  Builder  $query
     * @param  int  $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope: Filter to successful (completed) imports.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Filter to failed imports.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Determine the appropriate status based on row counts.
     *
     * @return string
     */
    public function computeStatus(): string
    {
        if ($this->failed_rows > 0 && $this->imported_rows === 0) {
            return self::STATUS_FAILED;
        }

        if ($this->failed_rows > 0 && $this->imported_rows > 0) {
            return self::STATUS_PARTIAL;
        }

        return self::STATUS_COMPLETED;
    }
}

// CLAUDE-CHECKPOINT
