<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Models\Bill;
use App\Models\Expense;
use App\Models\Invoice;

class ClientDocument extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_PENDING = 'pending_review';

    const STATUS_REVIEWED = 'reviewed';

    const STATUS_REJECTED = 'rejected';

    // Category constants
    const CATEGORY_INVOICE = 'invoice';

    const CATEGORY_RECEIPT = 'receipt';

    const CATEGORY_CONTRACT = 'contract';

    const CATEGORY_BANK_STATEMENT = 'bank_statement';

    const CATEGORY_OTHER = 'other';

    const CATEGORY_TAX_FORM = 'tax_form';

    const CATEGORY_PRODUCT_LIST = 'product_list';

    // Processing status constants
    const PROCESSING_PENDING = 'pending';

    const PROCESSING_CLASSIFYING = 'classifying';

    const PROCESSING_EXTRACTING = 'extracting';

    const PROCESSING_EXTRACTED = 'extracted';

    const PROCESSING_CONFIRMED = 'confirmed';

    const PROCESSING_FAILED = 'failed';

    // File constraints
    const ALLOWED_MIMETYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
    ];

    const MAX_FILE_SIZE = 10485760; // 10MB in bytes

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'uploaded_by',
        'partner_id',
        'category',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'processing_status',
        'ai_classification',
        'extracted_data',
        'linked_bill_id',
        'linked_expense_id',
        'linked_invoice_id',
        'extraction_method',
        'error_message',
        'reviewer_id',
        'reviewed_at',
        'notes',
        'rejection_reason',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'ai_classification' => 'array',
        'extracted_data' => 'array',
        'reviewed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Get the company that owns this document.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the user who reviewed this document.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the partner associated with this document.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Mark this document as reviewed.
     *
     * @param  int  $userId  The reviewer's user ID
     * @param  string|null  $notes  Optional review notes
     */
    public function markReviewed(int $userId, ?string $notes = null): bool
    {
        $this->status = self::STATUS_REVIEWED;
        $this->reviewer_id = $userId;
        $this->reviewed_at = now();
        $this->rejection_reason = null;

        if ($notes !== null) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * Reject this document with a reason.
     *
     * @param  int  $userId  The reviewer's user ID
     * @param  string  $reason  The rejection reason
     */
    public function reject(int $userId, string $reason): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->reviewer_id = $userId;
        $this->reviewed_at = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * Check if document is pending review.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get a temporary download URL for this document.
     */
    public function getDownloadUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        // Generate temporary signed URL (expires in 1 hour)
        try {
            return Storage::temporaryUrl($this->file_path, now()->addHour());
        } catch (\RuntimeException $e) {
            // Fallback for local disk that doesn't support temporary URLs
            return null;
        }
    }

    /**
     * Scope a query to only include pending documents.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include reviewed documents.
     */
    public function scopeReviewed($query)
    {
        return $query->where('status', self::STATUS_REVIEWED);
    }

    /**
     * Scope a query to only include rejected documents.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by partner.
     */
    public function scopeForPartner($query, int $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * Get the linked bill (created from extraction).
     */
    public function linkedBill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'linked_bill_id');
    }

    /**
     * Get the linked expense (created from extraction).
     */
    public function linkedExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'linked_expense_id');
    }

    /**
     * Get the linked invoice (created from extraction).
     */
    public function linkedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'linked_invoice_id');
    }

    /**
     * Scope: documents ready for accountant review.
     */
    public function scopeReadyForReview($query)
    {
        return $query->where('processing_status', self::PROCESSING_EXTRACTED);
    }

    /**
     * Scope: documents currently being processed by AI.
     */
    public function scopeProcessing($query)
    {
        return $query->whereIn('processing_status', [
            self::PROCESSING_CLASSIFYING,
            self::PROCESSING_EXTRACTING,
        ]);
    }

    /**
     * Scope: documents that failed AI processing.
     */
    public function scopeFailed($query)
    {
        return $query->where('processing_status', self::PROCESSING_FAILED);
    }

    /**
     * Check if the document has been processed by AI.
     */
    public function isExtracted(): bool
    {
        return $this->processing_status === self::PROCESSING_EXTRACTED;
    }

    /**
     * Check if the document is confirmed (bill/expense created).
     */
    public function isConfirmed(): bool
    {
        return $this->processing_status === self::PROCESSING_CONFIRMED;
    }

    /**
     * Delete the physical file when the model is force deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::forceDeleting(function ($document) {
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
        });
    }
}

