<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * InventoryDocument Model
 *
 * Represents formal inventory documents used in Macedonian accounting:
 * - Приемница (Receipt / Stock In document)
 * - Издатница (Issue / Stock Out document)
 * - Преносница (Transfer between warehouses)
 *
 * Documents follow a draft -> approved -> voided lifecycle.
 * Upon approval, stock movements are created via StockService.
 * Voiding reverses all associated stock movements.
 *
 * @property int $id
 * @property int $company_id
 * @property string $document_type
 * @property string $document_number
 * @property int $warehouse_id
 * @property int|null $destination_warehouse_id
 * @property \Carbon\Carbon $document_date
 * @property string $status
 * @property string|null $notes
 * @property int $total_value
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property int|null $created_by
 * @property array|null $meta
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InventoryDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Document type constants
    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_ISSUE = 'issue';

    public const TYPE_TRANSFER = 'transfer';

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_VOIDED = 'voided';

    /**
     * Document number prefixes per type (Macedonian conventions).
     */
    protected static array $prefixes = [
        self::TYPE_RECEIPT => 'PR',
        self::TYPE_ISSUE => 'IZ',
        self::TYPE_TRANSFER => 'PN',
    ];

    /**
     * The "booted" method of the model.
     * Auto-generates document_number on creating event.
     */
    protected static function booted(): void
    {
        static::creating(function (InventoryDocument $doc) {
            if (empty($doc->document_number)) {
                $doc->document_number = static::generateDocumentNumber(
                    $doc->company_id,
                    $doc->document_type
                );
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'approved_at' => 'datetime',
            'meta' => 'array',
            'total_value' => 'integer',
        ];
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the company that owns this document.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the source warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the destination warehouse (transfers only).
     */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /**
     * Get the line items for this document.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryDocumentItem::class);
    }

    /**
     * Get the user who approved this document.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this document.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Scope to filter by company using request header.
     */
    public function scopeWhereCompany($query)
    {
        return $query->where('inventory_documents.company_id', request()->header('company'));
    }

    /**
     * Scope to filter by document type.
     */
    public function scopeWhereType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeWhereDateRange($query, ?string $fromDate, ?string $toDate)
    {
        if ($fromDate) {
            $query->where('document_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('document_date', '<=', $toDate);
        }

        return $query;
    }

    // ==========================================
    // Helpers
    // ==========================================

    /**
     * Check if document is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if document is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if document is voided.
     */
    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /**
     * Generate a sequential document number for the given company, type, and current year.
     *
     * Format: PREFIX-YYYY-NNNN (e.g., PR-2025-0001, IZ-2025-0042, PN-2025-0003)
     *
     * @param  int  $companyId  The company ID
     * @param  string  $type  The document type (receipt, issue, transfer)
     * @return string The generated document number
     */
    public static function generateDocumentNumber(int $companyId, string $type): string
    {
        $prefix = static::$prefixes[$type] ?? 'DOC';
        $year = now()->year;

        $pattern = "{$prefix}-{$year}-%";

        $lastDoc = static::where('company_id', $companyId)
            ->where('document_number', 'like', $pattern)
            ->orderByRaw('CAST(SUBSTRING_INDEX(document_number, "-", -1) AS UNSIGNED) DESC')
            ->first();

        $nextNumber = 1;
        if ($lastDoc) {
            $parts = explode('-', $lastDoc->document_number);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        }

        return sprintf('%s-%d-%04d', $prefix, $year, $nextNumber);
    }

    /**
     * Get human-readable document type label (Macedonian).
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_RECEIPT => 'Приемница',
            self::TYPE_ISSUE => 'Издатница',
            self::TYPE_TRANSFER => 'Преносница',
        ];

        return $labels[$this->document_type] ?? $this->document_type;
    }

    /**
     * Get human-readable status label (Macedonian).
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_DRAFT => 'Нацрт',
            self::STATUS_APPROVED => 'Одобрен',
            self::STATUS_VOIDED => 'Поништен',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
