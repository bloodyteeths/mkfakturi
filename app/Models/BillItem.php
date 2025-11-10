<?php

namespace App\Models;

use App\Traits\HasCustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Traits\HasAuditing;

class BillItem extends Model 
{
    use HasAuditing;
    use HasCustomFieldsTrait;
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'float',
            'discount' => 'float',
            'discount_val' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
            'exchange_rate' => 'float',
            'base_price' => 'integer',
            'base_discount_val' => 'integer',
            'base_tax' => 'integer',
            'base_total' => 'integer',
        ];
    }

    /**
     * Relationship: BillItem belongs to Bill
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Relationship: BillItem belongs to Item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Relationship: BillItem has many Taxes (morphMany)
     */
    public function taxes(): MorphMany
    {
        return $this->morphMany(Tax::class, 'taxable');
    }

    /**
     * Relationship: BillItem has many custom field values (morphMany)
     */
    public function fields(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'custom_field_valuable');
    }

    /**
     * Relationship: BillItem belongs to Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

// CLAUDE-CHECKPOINT
