<?php

namespace Modules\Mk\Models\Manufacturing;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use App\Traits\BelongsToCompany;
use App\Traits\HasAuditing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends Model
{
    use BelongsToCompany;
    use HasAuditing;
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\ProductionOrderFactory::new();
    }

    protected $table = 'production_orders';

    const STATUS_DRAFT = 'draft';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'currency_id',
        'bom_id',
        'order_number',
        'order_date',
        'expected_completion_date',
        'completed_at',
        'status',
        'output_item_id',
        'planned_quantity',
        'actual_quantity',
        'output_warehouse_id',
        'work_center_id',
        'total_material_cost',
        'total_labor_cost',
        'total_overhead_cost',
        'total_wastage_cost',
        'total_production_cost',
        'cost_per_unit',
        'material_variance',
        'labor_variance',
        'total_variance',
        'notes',
        'meta',
        'ifrs_transaction_id',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_completion_date' => 'date',
            'completed_at' => 'datetime',
            'planned_quantity' => 'decimal:4',
            'actual_quantity' => 'decimal:4',
            'total_material_cost' => 'integer',
            'total_labor_cost' => 'integer',
            'total_overhead_cost' => 'integer',
            'total_wastage_cost' => 'integer',
            'total_production_cost' => 'integer',
            'cost_per_unit' => 'integer',
            'material_variance' => 'integer',
            'labor_variance' => 'integer',
            'total_variance' => 'integer',
            'meta' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProductionOrder $order) {
            if (empty($order->order_number)) {
                $year = date('Y');
                $sequence = static::where('company_id', $order->company_id)
                    ->withTrashed()
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $order->order_number = sprintf('РН-%d-%04d', $year, $sequence);
            }
        });
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function outputItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'output_item_id');
    }

    public function outputWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'output_warehouse_id');
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterial::class);
    }

    public function laborEntries(): HasMany
    {
        return $this->hasMany(ProductionOrderLabor::class);
    }

    public function overheadEntries(): HasMany
    {
        return $this->hasMany(ProductionOrderOverhead::class);
    }

    public function coProductionOutputs(): HasMany
    {
        return $this->hasMany(CoProductionOutput::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ---- Scopes ----

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('production_orders.status', $status);
    }

    public function scopeWhereSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('production_orders.order_number', 'LIKE', "%{$search}%")
              ->orWhereHas('outputItem', function ($iq) use ($search) {
                  $iq->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('status')) {
            $query->byStatus($filters->get('status'));
        }

        if ($filters->get('bom_id')) {
            $query->where('bom_id', $filters->get('bom_id'));
        }

        if ($filters->get('output_item_id')) {
            $query->where('output_item_id', $filters->get('output_item_id'));
        }

        if ($filters->get('from_date')) {
            $query->where('order_date', '>=', $filters->get('from_date'));
        }

        if ($filters->get('to_date')) {
            $query->where('order_date', '<=', $filters->get('to_date'));
        }

        return $query;
    }

    // ---- Status Helpers ----

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canStart(): bool
    {
        return $this->isDraft();
    }

    public function canComplete(): bool
    {
        return $this->isInProgress();
    }

    public function canCancel(): bool
    {
        return ! $this->isCompleted();
    }

    public function canEdit(): bool
    {
        return $this->isDraft();
    }

    // ---- Cost Calculation ----

    /**
     * Recalculate all cost totals from child records.
     */
    public function recalculateCosts(): void
    {
        $this->total_material_cost = $this->materials()->sum('actual_total_cost');
        $this->total_labor_cost = $this->laborEntries()->sum('total_cost');
        $this->total_overhead_cost = $this->overheadEntries()->sum('amount');
        $this->total_wastage_cost = $this->materials()->sum('wastage_cost');

        $this->total_production_cost = $this->total_material_cost
            + $this->total_labor_cost
            + $this->total_overhead_cost;

        $actualQty = (float) $this->actual_quantity;
        $this->cost_per_unit = $actualQty > 0
            ? (int) round($this->total_production_cost / $actualQty)
            : 0;

        $this->saveQuietly();
    }

    /**
     * Calculate variance against BOM normative cost.
     */
    public function calculateVariances(): void
    {
        if (! $this->bom_id) {
            return;
        }

        $bom = $this->bom;
        if (! $bom) {
            return;
        }

        $normative = $bom->calculateNormativeCost();
        $actualQty = (float) $this->actual_quantity;

        $normativeMaterialTotal = (int) round($normative['material_cost'] * $actualQty);
        $normativeLaborTotal = (int) round($normative['labor_cost'] * $actualQty);
        $normativeTotal = (int) round($normative['total_cost'] * $actualQty);

        $this->material_variance = $this->total_material_cost - $normativeMaterialTotal;
        $this->labor_variance = $this->total_labor_cost - $normativeLaborTotal;
        $this->total_variance = $this->total_production_cost - $normativeTotal;

        $this->saveQuietly();
    }
}

// CLAUDE-CHECKPOINT
