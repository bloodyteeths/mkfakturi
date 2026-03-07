<?php

namespace Modules\Mk\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    protected $table = 'goods_receipt_items';

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'item_id',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'float',
            'quantity_accepted' => 'float',
            'quantity_rejected' => 'float',
        ];
    }

    // ---- Relationships ----

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

// CLAUDE-CHECKPOINT
