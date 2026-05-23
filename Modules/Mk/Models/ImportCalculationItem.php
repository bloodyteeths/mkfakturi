<?php

namespace Modules\Mk\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportCalculationItem extends Model
{
    protected $table = 'import_calculation_items';

    protected $fillable = [
        'import_calculation_id',
        'item_id',
        'tariff_heading',
        'description',
        'quantity',
        'unit',
        'unit_price_fcy',
        'invoice_value_fcy',
        'invoice_value_mkd',
        'transport_allocated',
        'customs_base',
        'customs_duty_rate',
        'customs_duty_amount',
        'forwarding_allocated',
        'other_costs_allocated',
        'landed_cost_before_vat',
        'import_vat_amount',
        'total_landed_cost',
        'unit_landed_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'customs_duty_rate' => 'decimal:2',
            'unit_price_fcy' => 'integer',
            'invoice_value_fcy' => 'integer',
            'invoice_value_mkd' => 'integer',
            'transport_allocated' => 'integer',
            'customs_base' => 'integer',
            'customs_duty_amount' => 'integer',
            'forwarding_allocated' => 'integer',
            'other_costs_allocated' => 'integer',
            'landed_cost_before_vat' => 'integer',
            'import_vat_amount' => 'integer',
            'total_landed_cost' => 'integer',
            'unit_landed_cost' => 'integer',
        ];
    }

    public function importCalculation(): BelongsTo
    {
        return $this->belongsTo(ImportCalculation::class, 'import_calculation_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

// CLAUDE-CHECKPOINT
