<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payment Batch Model
 *
 * Represents a batch of payment orders (Налог за плаќање) that can be
 * exported as PP30, SEPA pain.001, or CSV for submission to banks.
 *
 * @property int $id
 * @property int $company_id
 * @property string $batch_number
 * @property \Carbon\Carbon $batch_date
 * @property int|null $bank_account_id
 * @property string $format pp30|pp50|sepa_sct|csv
 * @property string $status draft|pending_approval|approved|exported|sent_to_bank|confirmed|cancelled
 * @property int $total_amount Amount in cents
 * @property int $item_count
 * @property int|null $currency_id
 * @property \Carbon\Carbon|null $exported_at
 * @property string|null $exported_file_path
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 */
class PaymentBatch extends Model
{
    use SoftDeletes;

    protected $table = 'payment_batches';

    protected $fillable = [
        'company_id',
        'batch_number',
        'batch_date',
        'bank_account_id',
        'format',
        'status',
        'total_amount',
        'item_count',
        'currency_id',
        'exported_at',
        'exported_file_path',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'batch_date' => 'date',
            'total_amount' => 'integer',
            'item_count' => 'integer',
            'exported_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_EXPORTED = 'exported';

    public const STATUS_SENT_TO_BANK = 'sent_to_bank';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    // Format constants
    public const FORMAT_PP30 = 'pp30';

    public const FORMAT_PP50 = 'pp50';

    public const FORMAT_SEPA_SCT = 'sepa_sct';

    public const FORMAT_CSV = 'csv';

    /**
     * Boot the model: auto-generate batch_number on creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $batch) {
            if (empty($batch->batch_number)) {
                $batch->batch_number = static::generateBatchNumber($batch->company_id);
            }
        });
    }

    /**
     * Generate a unique batch number: NAL-{year}-{sequence}
     */
    public static function generateBatchNumber(int $companyId): string
    {
        $year = date('Y');
        $lastBatch = static::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        if ($lastBatch && preg_match('/NAL-' . $year . '-(\d+)/', $lastBatch->batch_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('NAL-%s-%04d', $year, $sequence);
    }

    // ----- Relationships -----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentBatchItem::class, 'payment_batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BankAccount::class, 'bank_account_id');
    }

    // ----- Scopes -----

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('payment_batches.company_id', $companyId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('payment_batches.status', $status);
    }

    // ----- Helpers -----

    /**
     * Get formatted total amount (amount / 100).
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount / 100, 2, '.', '');
    }

    /**
     * Check if batch can be edited.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL]);
    }

    /**
     * Check if batch can be approved.
     */
    public function isApprovable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL]);
    }

    /**
     * Check if batch can be exported.
     */
    public function isExportable(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_EXPORTED]);
    }

    /**
     * Check if batch can be confirmed.
     */
    public function isConfirmable(): bool
    {
        return in_array($this->status, [self::STATUS_EXPORTED, self::STATUS_SENT_TO_BANK]);
    }

    /**
     * Check if batch can be cancelled.
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL, self::STATUS_APPROVED]);
    }
}

// CLAUDE-CHECKPOINT
