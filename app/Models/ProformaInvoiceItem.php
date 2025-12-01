<?php

namespace App\Models;

use App\Traits\HasCustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProformaInvoiceItem extends Model
{
    use HasCustomFieldsTrait;
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'discount_type',
        'price',
        'quantity',
        'discount',
        'discount_val',
        'tax',
        'total',
        'unit_name',
        'exchange_rate',
        'base_price',
        'base_discount_val',
        'base_tax',
        'base_total',
        'proforma_invoice_id',
        'item_id',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'total' => 'integer',
            'discount' => 'float',
            'quantity' => 'float',
            'discount_val' => 'integer',
            'tax' => 'integer',
            'base_price' => 'integer',
            'base_discount_val' => 'integer',
            'base_tax' => 'integer',
            'base_total' => 'integer',
            'exchange_rate' => 'float',
        ];
    }

    // Relationships

    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function fields(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'custom_field_valuable');
    }

    // Scopes

    public function scopeWhereCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeProformaInvoicesBetween($query, $start, $end)
    {
        $query->whereHas('proformaInvoice', function ($query) use ($start, $end) {
            $query->whereBetween(
                'proforma_invoice_date',
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            );
        });
    }
}

// CLAUDE-CHECKPOINT
