<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Account Suggestion Feedback Model
 *
 * Tracks historical accuracy of AI-powered account suggestions
 * to enable confidence calibration based on actual user acceptance rates.
 *
 * @property int $id
 * @property int $company_id
 * @property string $entity_type
 * @property string $suggestion_reason
 * @property int $suggested_account_id
 * @property int|null $accepted_account_id
 * @property float $original_confidence
 * @property bool $was_accepted
 * @property bool $was_modified
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AccountSuggestionFeedback extends Model
{
    use HasFactory;

    protected $table = 'account_suggestion_feedback';

    protected $fillable = [
        'company_id',
        'entity_type',
        'suggestion_reason',
        'suggested_account_id',
        'accepted_account_id',
        'original_confidence',
        'was_accepted',
        'was_modified',
    ];

    protected $casts = [
        'original_confidence' => 'float',
        'was_accepted' => 'boolean',
        'was_modified' => 'boolean',
    ];

    /**
     * Get the company that owns the feedback.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the suggested account.
     */
    public function suggestedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'suggested_account_id');
    }

    /**
     * Get the accepted account.
     */
    public function acceptedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accepted_account_id');
    }

    /**
     * Scope to filter by company.
     */
    public function scopeWhereCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by entity type.
     */
    public function scopeWhereEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope to filter by suggestion reason.
     */
    public function scopeWhereSuggestionReason($query, string $reason)
    {
        return $query->where('suggestion_reason', $reason);
    }

    /**
     * Scope to get recent feedback.
     */
    public function scopeRecent($query, int $limit = 100)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Calculate acceptance rate for a specific combination of company, entity type, and reason.
     *
     * @param int $companyId
     * @param string $entityType
     * @param string $reason
     * @param int $limit Number of recent records to analyze (default: 100)
     * @return array ['rate' => float, 'sample_size' => int]
     */
    public static function calculateAccuracyRate(
        int $companyId,
        string $entityType,
        string $reason,
        int $limit = 100
    ): array {
        $feedback = self::whereCompany($companyId)
            ->whereEntityType($entityType)
            ->whereSuggestionReason($reason)
            ->recent($limit)
            ->get();

        $sampleSize = $feedback->count();

        if ($sampleSize === 0) {
            return [
                'rate' => 0.5, // Neutral fallback
                'sample_size' => 0,
            ];
        }

        $acceptedCount = $feedback->where('was_accepted', true)->count();
        $rate = $acceptedCount / $sampleSize;

        return [
            'rate' => round($rate, 3),
            'sample_size' => $sampleSize,
        ];
    }

    /**
     * Get accuracy statistics grouped by entity type and reason.
     *
     * @param int $companyId
     * @return array
     */
    public static function getAccuracyStatistics(int $companyId): array
    {
        return self::whereCompany($companyId)
            ->select([
                'entity_type',
                'suggestion_reason',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN was_accepted = 1 THEN 1 ELSE 0 END) as accepted'),
                DB::raw('ROUND(SUM(CASE WHEN was_accepted = 1 THEN 1 ELSE 0 END) / COUNT(*), 3) as acceptance_rate'),
                DB::raw('AVG(original_confidence) as avg_confidence'),
            ])
            ->groupBy('entity_type', 'suggestion_reason')
            ->having('total', '>=', 10) // Only show stats with sufficient data
            ->orderBy('acceptance_rate', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get recent feedback performance trends.
     *
     * @param int $companyId
     * @param int $days Number of days to look back
     * @return array
     */
    public static function getPerformanceTrend(int $companyId, int $days = 30): array
    {
        return self::whereCompany($companyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_suggestions'),
                DB::raw('SUM(CASE WHEN was_accepted = 1 THEN 1 ELSE 0 END) as accepted_suggestions'),
                DB::raw('ROUND(SUM(CASE WHEN was_accepted = 1 THEN 1 ELSE 0 END) / COUNT(*), 3) as daily_acceptance_rate'),
            ])
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }
}
// CLAUDE-CHECKPOINT
