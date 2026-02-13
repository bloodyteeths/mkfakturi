<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fiscal Year Model
 *
 * Represents a fiscal year for a company. Used by the year-end closing wizard
 * to track which years have been closed and by whom.
 *
 * @property int $id
 * @property int $company_id
 * @property int $year
 * @property string $status OPEN, CLOSING, or CLOSED
 * @property \Carbon\Carbon|null $closed_at
 * @property int|null $closed_by
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FiscalYear extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_CLOSING = 'CLOSING';

    public const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'company_id',
        'year',
        'status',
        'closed_at',
        'closed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function scopeWhereCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where('company_id', $companyId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Get or create a fiscal year record for a company.
     */
    public static function getOrCreate(int $companyId, int $year): self
    {
        return self::firstOrCreate(
            ['company_id' => $companyId, 'year' => $year],
            ['status' => self::STATUS_OPEN]
        );
    }
}
// CLAUDE-CHECKPOINT
