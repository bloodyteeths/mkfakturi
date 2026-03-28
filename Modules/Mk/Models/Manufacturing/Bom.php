<?php

namespace Modules\Mk\Models\Manufacturing;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use App\Traits\BelongsToCompany;
use App\Traits\HasAuditing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bom extends Model
{
    use BelongsToCompany;
    use HasAuditing;
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\BomFactory::new();
    }

    protected $table = 'boms';

    protected $fillable = [
        'company_id',
        'currency_id',
        'name',
        'code',
        'output_item_id',
        'output_quantity',
        'output_unit_id',
        'description',
        'expected_wastage_percent',
        'labor_cost_per_unit',
        'overhead_cost_per_unit',
        'is_active',
        'version',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'output_quantity' => 'decimal:4',
            'expected_wastage_percent' => 'decimal:2',
            'labor_cost_per_unit' => 'integer',
            'overhead_cost_per_unit' => 'integer',
            'is_active' => 'boolean',
            'version' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Bom $bom) {
            if (empty($bom->code)) {
                $year = date('Y');
                $sequence = static::where('company_id', $bom->company_id)
                    ->withTrashed()
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $bom->code = sprintf('BOM-%d-%04d', $year, $sequence);
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

    public function outputItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'output_item_id');
    }

    public function outputUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'output_unit_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BomLine::class)->orderBy('sort_order');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ProductionTemplate::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---- Scopes ----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWhereSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('boms.name', 'LIKE', "%{$search}%")
              ->orWhere('boms.code', 'LIKE', "%{$search}%");
        });
    }

    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->has('is_active') && $filters->get('is_active') !== '') {
            $query->where('is_active', (bool) $filters->get('is_active'));
        }

        if ($filters->get('output_item_id')) {
            $query->where('output_item_id', $filters->get('output_item_id'));
        }

        return $query;
    }

    // ---- Business Methods ----

    /**
     * Calculate the normative cost per unit of output from current WAC prices.
     *
     * @return array{material_cost: int, labor_cost: int, overhead_cost: int, total_cost: int}
     */
    public function calculateNormativeCost(): array
    {
        $outputQty = $this->output_quantity > 0 ? (float) $this->output_quantity : 1;
        $materialCost = 0;

        foreach ($this->lines as $line) {
            $item = $line->item;
            $wac = $item ? $this->getItemWac($item) : 0;

            $qtyPerUnit = (float) $line->quantity / $outputQty;
            $wastageMultiplier = 1 + ((float) $line->wastage_percent / 100);
            $adjustedQty = $qtyPerUnit * $wastageMultiplier;
            $materialCost += (int) round($adjustedQty * $wac);
        }

        return [
            'material_cost' => $materialCost,
            'labor_cost' => (int) $this->labor_cost_per_unit,
            'overhead_cost' => (int) $this->overhead_cost_per_unit,
            'total_cost' => $materialCost + (int) $this->labor_cost_per_unit + (int) $this->overhead_cost_per_unit,
        ];
    }

    /**
     * Duplicate this BOM as a new version.
     */
    public function duplicate(): self
    {
        $newBom = $this->replicate(['code', 'approved_by', 'approved_at']);
        $newBom->version = $this->version + 1;
        $newBom->code = null; // Auto-generate
        $newBom->save();

        foreach ($this->lines as $line) {
            $newLine = $line->replicate();
            $newLine->bom_id = $newBom->id;
            $newLine->save();
        }

        return $newBom->fresh(['lines']);
    }

    /**
     * Check if this BOM is used by any production orders.
     */
    public function isUsedByOrders(): bool
    {
        return $this->productionOrders()->exists();
    }

    /**
     * Get WAC for an item from stock service.
     */
    protected function getItemWac(Item $item): int
    {
        $stockService = app(\App\Services\StockService::class);
        $stock = $stockService->getItemStock($item->id, $this->company_id);

        return $stock['weighted_average_cost'] ?? 0;
    }
}

// CLAUDE-CHECKPOINT
