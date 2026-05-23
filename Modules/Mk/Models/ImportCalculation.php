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

class ImportCalculation extends Model
{
    use SoftDeletes;

    protected $table = 'import_calculations';

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'company_id',
        'document_number',
        'document_date',
        'status',
        'supplier_bill_id',
        'supplier_name',
        'supplier_invoice_number',
        'currency_code',
        'exchange_rate',
        'warehouse_id',
        'transport_amount',
        'forwarding_amount',
        'other_costs_amount',
        'customs_duty_total',
        'import_vat_total',
        'total_landed_cost',
        'total_invoice_value_mkd',
        'vat_rate',
        'notes',
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
            'exchange_rate' => 'decimal:6',
            'vat_rate' => 'decimal:2',
            'transport_amount' => 'integer',
            'forwarding_amount' => 'integer',
            'other_costs_amount' => 'integer',
            'customs_duty_total' => 'integer',
            'import_vat_total' => 'integer',
            'total_landed_cost' => 'integer',
            'total_invoice_value_mkd' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ImportCalculation $calc) {
            if (empty($calc->document_number)) {
                $year = date('Y');
                $sequence = static::where('company_id', $calc->company_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $calc->document_number = sprintf('VK-%d-%04d', $year, $sequence);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ImportCalculationItem::class, 'import_calculation_id');
    }

    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'supplier_bill_id');
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

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Нацрт',
            self::STATUS_APPROVED => 'Одобрена',
            self::STATUS_VOIDED => 'Поништена',
            default => $this->status,
        };
    }

    public function getSupplierDisplayNameAttribute(): string
    {
        if ($this->supplierBill && $this->supplierBill->supplier) {
            return $this->supplierBill->supplier->name;
        }

        return $this->supplier_name ?? '';
    }
}

// CLAUDE-CHECKPOINT
