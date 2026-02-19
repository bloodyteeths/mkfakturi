<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InventoryDocumentItem Model
 *
 * Represents a single line item within an inventory document.
 * Each line references an inventory item with quantity and cost information.
 *
 * @property int $id
 * @property int $inventory_document_id
 * @property int $item_id
 * @property float $quantity
 * @property int|null $unit_cost Cost per unit in cents
 * @property int|null $total_cost Total cost in cents
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InventoryDocumentItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'integer',
            'total_cost' => 'integer',
        ];
    }

    /**
     * Get the parent inventory document.
     */
    public function inventoryDocument(): BelongsTo
    {
        return $this->belongsTo(InventoryDocument::class);
    }

    /**
     * Get the inventory item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
