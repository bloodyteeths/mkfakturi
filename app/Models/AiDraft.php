<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiDraft extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_USED = 'used';

    public const STATUS_EXPIRED = 'expired';

    public const ENTITY_INVOICE = 'invoice';

    public const ENTITY_BILL = 'bill';

    public const ENTITY_EXPENSE = 'expense';

    public const ENTITY_PAYMENT = 'payment';

    public const ENTITY_ESTIMATE = 'estimate';

    public const ENTITY_PROFORMA = 'proforma';

    public const ENTITY_CREDIT_NOTE = 'credit_note';

    public const ENTITY_RECURRING = 'recurring_invoice';

    public const VALID_ENTITIES = [
        self::ENTITY_INVOICE,
        self::ENTITY_BILL,
        self::ENTITY_EXPENSE,
        self::ENTITY_PAYMENT,
        self::ENTITY_ESTIMATE,
        self::ENTITY_PROFORMA,
        self::ENTITY_CREDIT_NOTE,
        self::ENTITY_RECURRING,
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'entity_type',
        'entity_data',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'entity_data' => 'array',
        'expires_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this draft is still usable.
     */
    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Mark draft as used.
     */
    public function markUsed(): void
    {
        $this->update(['status' => self::STATUS_USED]);
    }

    /**
     * Scope to pending, non-expired drafts.
     */
    public function scopeUsable($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
// CLAUDE-CHECKPOINT
