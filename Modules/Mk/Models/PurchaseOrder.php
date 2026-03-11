<?php

namespace Modules\Mk\Models;

use App\Models\Bill;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Modules\Mk\Models\CostCenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'company_id',
        'supplier_id',
        'po_number',
        'po_date',
        'expected_delivery_date',
        'status',
        'email_status',
        'email_sent_to',
        'sub_total',
        'tax',
        'total',
        'currency_id',
        'warehouse_id',
        'cost_center_id',
        'converted_bill_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'expected_delivery_date' => 'date',
            'sub_total' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
        ];
    }

    protected $appends = [
        'formattedTotal',
        'formattedSubTotal',
    ];

    /**
     * Boot method: auto-generate po_number on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PurchaseOrder $po) {
            if (empty($po->po_number)) {
                $year = date('Y');
                $sequence = static::where('company_id', $po->company_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $po->po_number = sprintf('PO-%d-%04d', $year, $sequence);
            }
        });
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedBill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'converted_bill_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    // ---- Scopes ----

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('purchase_orders.company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('purchase_orders.status', $status);
    }

    // ---- Accessors ----

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total / 100, 2, '.', ',');
    }

    public function getFormattedSubTotalAttribute(): string
    {
        return number_format($this->sub_total / 100, 2, '.', ',');
    }
}

// CLAUDE-CHECKPOINT
