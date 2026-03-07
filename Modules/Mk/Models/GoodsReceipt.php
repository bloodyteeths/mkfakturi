<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceipt extends Model
{
    use SoftDeletes;

    protected $table = 'goods_receipts';

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'receipt_number',
        'receipt_date',
        'warehouse_id',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
        ];
    }

    /**
     * Boot method: auto-generate receipt_number on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (GoodsReceipt $receipt) {
            if (empty($receipt->receipt_number)) {
                $year = date('Y');
                $sequence = static::where('company_id', $receipt->company_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $receipt->receipt_number = sprintf('GR-%d-%04d', $year, $sequence);
            }
        });
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---- Scopes ----

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('goods_receipts.company_id', $companyId);
    }
}

// CLAUDE-CHECKPOINT
