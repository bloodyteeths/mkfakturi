<?php

namespace Modules\Mk\Models;

use App\Models\Bill;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nivelacija extends Model
{
    use SoftDeletes;

    protected $table = 'nivelacii';

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_VOIDED = 'voided';

    const TYPE_PRICE_CHANGE = 'price_change';
    const TYPE_DISCOUNT = 'discount';
    const TYPE_SUPPLIER_CHANGE = 'supplier_change';

    protected $fillable = [
        'company_id',
        'document_number',
        'document_date',
        'type',
        'status',
        'reason',
        'source_bill_id',
        'warehouse_id',
        'total_difference',
        'approved_by',
        'approved_at',
        'created_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'approved_at' => 'datetime',
            'total_difference' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Nivelacija $nivelacija) {
            if (empty($nivelacija->document_number)) {
                $prefix = config('mk.pricing.nivelacija_prefix', 'NI');
                $year = date('Y');
                $sequence = static::where('company_id', $nivelacija->company_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $nivelacija->document_number = sprintf('%s-%d-%04d', $prefix, $year, $sequence);
            }
        });
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(NivelacijaItem::class, 'nivelacija_id');
    }

    public function sourceBill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'source_bill_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---- Status helpers ----

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    // ---- Scopes ----

    public function scopeWhereCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? (int) request()->header('company');

        return $query->where('company_id', $companyId);
    }

    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWhereDateRange($query, ?string $fromDate, ?string $toDate)
    {
        if ($fromDate) {
            $query->where('document_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('document_date', '<=', $toDate);
        }

        return $query;
    }

    // ---- Labels ----

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PRICE_CHANGE => 'Промена на цена',
            self::TYPE_DISCOUNT => 'Попуст',
            self::TYPE_SUPPLIER_CHANGE => 'Промена на добавувач',
            default => $this->type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Нацрт',
            self::STATUS_APPROVED => 'Одобрена',
            self::STATUS_VOIDED => 'Поништена',
            default => $this->status,
        };
    }
}

// CLAUDE-CHECKPOINT
