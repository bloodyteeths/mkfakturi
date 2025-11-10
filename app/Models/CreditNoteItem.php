<?php

namespace App\Models;

use App\Traits\HasCustomFieldsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * CreditNoteItem Model
 *
 * Represents individual line items on a credit note.
 * Supports both fixed and percentage discounts, with tax calculations similar to InvoiceItem.
 *
 * @package App\Models
 */
class CreditNoteItem extends Model
{
    use HasCustomFieldsTrait;
    use HasFactory;

    /**
     * Discount Type Constants
     */
    public const DISCOUNT_TYPE_FIXED = 'fixed';
    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    protected $guarded = [
        'id',
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
        ];
    }

    /**
     * Relationships
     */

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Scopes
     */

    public function scopeWhereCompany($query, $company_id)
    {
        $query->where('company_id', $company_id);
    }

    public function scopeCreditNotesBetween($query, $start, $end)
    {
        $query->whereHas('creditNote', function ($query) use ($start, $end) {
            $query->whereBetween(
                'credit_note_date',
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            );
        });
    }

    public function scopeApplyCreditNoteFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->creditNotesBetween($start, $end);
        }
    }

    public function scopeItemAttributes($query)
    {
        $query->select(
            DB::raw('sum(quantity) as total_quantity, sum(base_total) as total_amount, credit_note_items.name')
        )->groupBy('credit_note_items.name');
    }

    /**
     * Calculate item subtotal before tax
     *
     * @return float
     */
    public function calculateSubtotal(): float
    {
        $subtotal = $this->price * $this->quantity;

        // Apply discount
        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE) {
            $subtotal -= ($subtotal * ($this->discount / 100));
        } elseif ($this->discount_type === self::DISCOUNT_TYPE_FIXED) {
            $subtotal -= $this->discount_val;
        }

        return max(0, $subtotal);
    }

    /**
     * Calculate tax amount for this item
     *
     * @return float
     */
    public function calculateTaxAmount(): float
    {
        $subtotal = $this->calculateSubtotal();
        $taxAmount = 0;

        foreach ($this->taxes as $tax) {
            if ($tax->percent) {
                $taxAmount += ($subtotal * ($tax->percent / 100));
            }
        }

        return $taxAmount;
    }

    /**
     * Calculate total including tax
     *
     * @return float
     */
    public function calculateTotal(): float
    {
        return $this->calculateSubtotal() + $this->calculateTaxAmount();
    }
}

// CLAUDE-CHECKPOINT
