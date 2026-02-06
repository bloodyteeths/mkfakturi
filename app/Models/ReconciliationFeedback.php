<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReconciliationFeedback Model
 *
 * Stores user feedback on reconciliation matches to improve the matching
 * algorithm over time through machine learning.
 *
 * @property int $id
 * @property int $reconciliation_id
 * @property string $feedback
 * @property int|null $correct_invoice_id
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ReconciliationFeedback extends Model
{
    use HasFactory;

    /**
     * Feedback type constants
     */
    public const FEEDBACK_CORRECT = 'correct';

    public const FEEDBACK_WRONG = 'wrong';

    public const FEEDBACK_PARTIAL = 'partial';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'reconciliation_id',
        'feedback',
        'correct_invoice_id',
        'user_id',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reconciliation_feedback';

    /**
     * Get the reconciliation this feedback belongs to.
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    /**
     * Get the correct invoice if the match was wrong.
     */
    public function correctInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'correct_invoice_id');
    }

    /**
     * Get the user who provided the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get feedback by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $feedback
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFeedback($query, string $feedback)
    {
        return $query->where('feedback', $feedback);
    }

    /**
     * Scope: Get correct feedback.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCorrect($query)
    {
        return $query->where('feedback', self::FEEDBACK_CORRECT);
    }

    /**
     * Scope: Get wrong feedback.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWrong($query)
    {
        return $query->where('feedback', self::FEEDBACK_WRONG);
    }

    /**
     * Scope: Get partial feedback.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePartial($query)
    {
        return $query->where('feedback', self::FEEDBACK_PARTIAL);
    }

    /**
     * Check if feedback indicates the match was correct.
     */
    public function isCorrect(): bool
    {
        return $this->feedback === self::FEEDBACK_CORRECT;
    }

    /**
     * Check if feedback indicates the match was wrong.
     */
    public function isWrong(): bool
    {
        return $this->feedback === self::FEEDBACK_WRONG;
    }

    /**
     * Check if feedback indicates the match was partial.
     */
    public function isPartial(): bool
    {
        return $this->feedback === self::FEEDBACK_PARTIAL;
    }

    /**
     * Create a "correct" feedback for a reconciliation.
     *
     * @param  int  $reconciliationId
     * @param  int  $userId
     * @return static
     */
    public static function markCorrect(int $reconciliationId, int $userId): self
    {
        return self::create([
            'reconciliation_id' => $reconciliationId,
            'feedback' => self::FEEDBACK_CORRECT,
            'user_id' => $userId,
        ]);
    }

    /**
     * Create a "wrong" feedback for a reconciliation with correct invoice.
     *
     * @param  int  $reconciliationId
     * @param  int  $userId
     * @param  int|null  $correctInvoiceId
     * @return static
     */
    public static function markWrong(int $reconciliationId, int $userId, ?int $correctInvoiceId = null): self
    {
        return self::create([
            'reconciliation_id' => $reconciliationId,
            'feedback' => self::FEEDBACK_WRONG,
            'correct_invoice_id' => $correctInvoiceId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Create a "partial" feedback for a reconciliation.
     *
     * @param  int  $reconciliationId
     * @param  int  $userId
     * @return static
     */
    public static function markPartial(int $reconciliationId, int $userId): self
    {
        return self::create([
            'reconciliation_id' => $reconciliationId,
            'feedback' => self::FEEDBACK_PARTIAL,
            'user_id' => $userId,
        ]);
    }
}

// CLAUDE-CHECKPOINT
