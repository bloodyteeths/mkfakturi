<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse Model
 *
 * Represents a physical or logical warehouse for stock management.
 * Each company can have multiple warehouses with one designated as default.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $code
 * @property string|null $address
 * @property bool $is_default
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Warehouse extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the company that owns the warehouse.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all stock movements for this warehouse.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get all invoice items assigned to this warehouse.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get all bill items assigned to this warehouse.
     */
    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeWhereCompany($query)
    {
        return $query->where('warehouses.company_id', request()->header('company'));
    }

    /**
     * Scope to filter only active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get the default warehouse.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get or create default warehouse for a company.
     */
    public static function getOrCreateDefault(int $companyId): self
    {
        $warehouse = self::where('company_id', $companyId)
            ->where('is_default', true)
            ->first();

        if (! $warehouse) {
            $warehouse = self::create([
                'company_id' => $companyId,
                'name' => 'Default Warehouse',
                'code' => 'DEFAULT',
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        return $warehouse;
    }

    /**
     * Set this warehouse as the default (unset others).
     */
    public function setAsDefault(): void
    {
        // Unset other defaults for this company
        self::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get current stock quantity for an item in this warehouse.
     */
    public function getItemStock(int $itemId): float
    {
        $latestMovement = $this->stockMovements()
            ->where('item_id', $itemId)
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $latestMovement ? (float) $latestMovement->balance_quantity : 0;
    }

    /**
     * Get current stock value for an item in this warehouse.
     *
     * @return int Value in cents
     */
    public function getItemStockValue(int $itemId): int
    {
        $latestMovement = $this->stockMovements()
            ->where('item_id', $itemId)
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $latestMovement ? (int) $latestMovement->balance_value : 0;
    }
}
// CLAUDE-CHECKPOINT
