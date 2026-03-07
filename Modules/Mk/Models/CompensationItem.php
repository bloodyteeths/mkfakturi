<?php

namespace Modules\Mk\Models;

use App\Models\Bill;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompensationItem extends Model
{
    protected $table = 'compensation_items';

    protected $fillable = [
        'compensation_id',
        'side',
        'document_type',
        'document_id',
        'document_number',
        'document_date',
        'document_total',
        'amount_offset',
        'remaining_after',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'document_total' => 'integer',
            'amount_offset' => 'integer',
            'remaining_after' => 'integer',
        ];
    }

    protected $appends = [
        'formattedDocumentTotal',
        'formattedAmountOffset',
        'formattedRemainingAfter',
    ];

    // ---- Relationships ----

    public function compensation(): BelongsTo
    {
        return $this->belongsTo(Compensation::class);
    }

    /**
     * Get the related document (Invoice or Bill) based on document_type.
     * This is a manual morph-like relationship since document_type uses
     * short strings ('invoice', 'bill', 'credit_note') instead of class names.
     */
    public function document()
    {
        return match ($this->document_type) {
            'invoice', 'credit_note' => $this->belongsTo(Invoice::class, 'document_id'),
            'bill' => $this->belongsTo(Bill::class, 'document_id'),
            default => null,
        };
    }

    /**
     * Resolve the document model instance.
     */
    public function getDocumentModelAttribute()
    {
        return match ($this->document_type) {
            'invoice', 'credit_note' => Invoice::find($this->document_id),
            'bill' => Bill::find($this->document_id),
            default => null,
        };
    }

    // ---- Accessors ----

    public function getFormattedDocumentTotalAttribute(): string
    {
        return number_format($this->document_total / 100, 2, '.', ',');
    }

    public function getFormattedAmountOffsetAttribute(): string
    {
        return number_format($this->amount_offset / 100, 2, '.', ',');
    }

    public function getFormattedRemainingAfterAttribute(): string
    {
        return number_format($this->remaining_after / 100, 2, '.', ',');
    }
}

// CLAUDE-CHECKPOINT
