<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * StockMovement Model
 *
 * Records every stock in/out movement for inventory tracking.
 * Used for weighted average cost calculation and stock history.
 *
 * Source types:
 * - 'initial': Initial stock setup
 * - 'bill_item': Stock IN from purchase/bill
 * - 'invoice_item': Stock OUT from sale/invoice
 * - 'adjustment': Manual stock adjustment
 * - 'transfer_in': Transfer from another warehouse
 * - 'transfer_out': Transfer to another warehouse
 *
 * @property int $id
 * @property int $company_id
 * @property int $warehouse_id
 * @property int $item_id
 * @property string $source_type
 * @property int|null $source_id
 * @property float $quantity Positive for IN, negative for OUT
 * @property int|null $unit_cost Cost per unit in cents
 * @property int|null $total_cost Total cost in cents
 * @property \Carbon\Carbon $movement_date
 * @property string|null $notes
 * @property float $balance_quantity Running balance after this movement
 * @property int $balance_value Running total value in cents
 * @property array|null $meta Additional metadata
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class StockMovement extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Source type constants
    public const SOURCE_INITIAL = 'initial';
    public const SOURCE_BILL_ITEM = 'bill_item';
    public const SOURCE_INVOICE_ITEM = 'invoice_item';
    public const SOURCE_ADJUSTMENT = 'adjustment';
    public const SOURCE_TRANSFER_IN = 'transfer_in';
    public const SOURCE_TRANSFER_OUT = 'transfer_out';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'integer',
            'total_cost' => 'integer',
            'movement_date' => 'date',
            'balance_quantity' => 'decimal:4',
            'balance_value' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * Get the company that owns the movement.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the warehouse for this movement.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the item for this movement.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the user who created this movement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the source document (polymorphic).
     */
    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    /**
     * Scope to filter by company.
     */
    public function scopeWhereCompany($query)
    {
        return $query->where('stock_movements.company_id', request()->header('company'));
    }

    /**
     * Scope to filter by warehouse.
     */
    public function scopeWhereWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope to filter by item.
     */
    public function scopeWhereItem($query, int $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeWhereDateRange($query, ?string $fromDate, ?string $toDate)
    {
        if ($fromDate) {
            $query->where('movement_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('movement_date', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Scope to filter by source type.
     */
    public function scopeWhereSourceType($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Scope for stock IN movements (positive quantity).
     */
    public function scopeStockIn($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope for stock OUT movements (negative quantity).
     */
    public function scopeStockOut($query)
    {
        return $query->where('quantity', '<', 0);
    }

    /**
     * Check if this is a stock IN movement.
     */
    public function isStockIn(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Check if this is a stock OUT movement.
     */
    public function isStockOut(): bool
    {
        return $this->quantity < 0;
    }

    /**
     * Get the absolute quantity (always positive).
     */
    public function getAbsoluteQuantityAttribute(): float
    {
        return abs($this->quantity);
    }

    /**
     * Get human-readable source type label.
     */
    public function getSourceTypeLabelAttribute(): string
    {
        $labels = [
            self::SOURCE_INITIAL => 'Initial Stock',
            self::SOURCE_BILL_ITEM => 'Purchase',
            self::SOURCE_INVOICE_ITEM => 'Sale',
            self::SOURCE_ADJUSTMENT => 'Adjustment',
            self::SOURCE_TRANSFER_IN => 'Transfer In',
            self::SOURCE_TRANSFER_OUT => 'Transfer Out',
        ];

        return $labels[$this->source_type] ?? $this->source_type;
    }

    /**
     * Get the weighted average unit cost at this point in time.
     * Calculated as balance_value / balance_quantity.
     */
    public function getWeightedAverageCostAttribute(): ?int
    {
        if ($this->balance_quantity == 0) {
            return null;
        }

        return (int) round($this->balance_value / $this->balance_quantity);
    }
}
// CLAUDE-CHECKPOINT
