<?php

namespace App\Models;

use App\Traits\CacheableTrait;
use App\Traits\GeneratesPdfTrait;
use App\Traits\HasCustomFieldsTrait;
use App\Traits\RequiresApproval;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditing;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Bill extends Model implements HasMedia
{
    use HasAuditing;
    use CacheableTrait;
    use GeneratesPdfTrait;
    use HasCustomFieldsTrait;
    use HasFactory;
    use InteractsWithMedia;
    use RequiresApproval;
    use SoftDeletes;

    // Status constants
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SENT = 'SENT';
    public const STATUS_VIEWED = 'VIEWED';
    public const STATUS_OVERDUE = 'OVERDUE';
    public const STATUS_PAID = 'PAID';
    public const STATUS_COMPLETED = 'COMPLETED';

    // Paid status constants
    public const PAID_STATUS_UNPAID = 'UNPAID';
    public const PAID_STATUS_PAID = 'PAID';
    public const PAID_STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';

    protected $guarded = [
        'id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'bill_date',
        'due_date',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'formattedBillDate',
        'formattedDueDate',
        'dueAmount',
        'allowEdit',
    ];

    /**
     * Default eager loaded relationships
     */
    protected $with = [
        'supplier:id,name,email',
        'currency:id,name,code,symbol',
        'company:id,name'
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'tax' => 'integer',
            'sub_total' => 'integer',
            'discount' => 'float',
            'discount_val' => 'integer',
            'exchange_rate' => 'float',
            'due_amount' => 'integer',
            'posted_to_ifrs' => 'boolean',
        ];
    }

    /**
     * Get formatted created at date
     */
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        return Carbon::parse($this->created_at)->format($dateFormat);
    }

    /**
     * Get formatted bill date
     */
    public function getFormattedBillDateAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        return Carbon::parse($this->bill_date)->translatedFormat($dateFormat);
    }

    /**
     * Get formatted due date
     */
    public function getFormattedDueDateAttribute()
    {
        if (!$this->due_date) {
            return null;
        }
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        return Carbon::parse($this->due_date)->translatedFormat($dateFormat);
    }

    /**
     * Get due amount (total - paid)
     */
    public function getDueAmountAttribute()
    {
        $paidAmount = $this->payments()->sum('amount');
        return $this->total - $paidAmount;
    }

    /**
     * Check if bill can be edited
     */
    public function getAllowEditAttribute()
    {
        // If posted to IFRS, cannot edit
        if ($this->posted_to_ifrs) {
            return false;
        }

        $retrospective_edit = CompanySetting::getSetting('retrospective_edits', $this->company_id);

        $allowed = true;

        $status = [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_VIEWED,
            self::STATUS_COMPLETED,
        ];

        if ($retrospective_edit == 'disable_on_invoice_sent' && (in_array($this->status, $status)) && ($this->paid_status === self::PAID_STATUS_PARTIALLY_PAID || $this->paid_status === self::PAID_STATUS_PAID)) {
            $allowed = false;
        } elseif ($retrospective_edit == 'disable_on_invoice_partial_paid' && ($this->paid_status === self::PAID_STATUS_PARTIALLY_PAID || $this->paid_status === self::PAID_STATUS_PAID)) {
            $allowed = false;
        } elseif ($retrospective_edit == 'disable_on_invoice_paid' && $this->paid_status === self::PAID_STATUS_PAID) {
            $allowed = false;
        }

        return $allowed;
    }

    /**
     * Relationship: Bill belongs to Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship: Bill belongs to Supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relationship: Bill was created by User
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Relationship: Bill has many BillItems
     */
    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    /**
     * Relationship: Bill has many Taxes (morphMany)
     */
    public function taxes(): MorphMany
    {
        return $this->morphMany(Tax::class, 'taxable');
    }

    /**
     * Relationship: Bill has many BillPayments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    /**
     * Relationship: Bill belongs to Currency
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Relationship: Bill has many custom field values (morphMany)
     */
    public function fields(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'custom_field_valuable');
    }

    /**
     * Get the tax report period this bill belongs to based on bill_date
     */
    public function taxReportPeriod(): BelongsTo
    {
        // Find the period that contains this bill's date
        return $this->belongsTo(TaxReportPeriod::class, 'tax_report_period_id');
    }

    /**
     * Scope: Filter by company
     */
    public function scopeWhereCompany($query)
    {
        $query->where('bills.company_id', request()->header('company'));
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWhereStatus($query, $status)
    {
        return $query->where('bills.status', $status);
    }

    /**
     * Scope: Filter by paid status
     */
    public function scopeWherePaidStatus($query, $status)
    {
        return $query->where('bills.paid_status', $status);
    }

    /**
     * Scope: Filter by bill number
     */
    public function scopeWhereBillNumber($query, $billNumber)
    {
        return $query->where('bills.bill_number', 'LIKE', '%'.$billNumber.'%');
    }

    /**
     * Scope: Filter bills between dates
     */
    public function scopeBillsBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'bills.bill_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    /**
     * Scope: Filter by due date
     */
    public function scopeWhereDueDate($query, $start, $end)
    {
        return $query->whereBetween('bills.due_date', [$start, $end]);
    }

    /**
     * Scope: Filter by supplier
     */
    public function scopeWhereSupplier($query, $supplierId)
    {
        return $query->where('bills.supplier_id', $supplierId);
    }

    /**
     * Scope: Search across multiple fields
     */
    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->whereHas('supplier', function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('contact_name', 'LIKE', '%'.$term.'%');
            });
        }
    }

    /**
     * Scope: Order results
     */
    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    /**
     * Scope: Apply filters
     */
    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters)->filter()->all();

        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereSearch($search);
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->whereStatus($status);
        })->when($filters['paid_status'] ?? null, function ($query, $paidStatus) {
            $query->wherePaidStatus($paidStatus);
        })->when($filters['bill_number'] ?? null, function ($query, $billNumber) {
            $query->whereBillNumber($billNumber);
        })->when(($filters['from_date'] ?? null) && ($filters['to_date'] ?? null), function ($query) use ($filters) {
            $start = Carbon::parse($filters['from_date']);
            $end = Carbon::parse($filters['to_date']);
            $query->billsBetween($start, $end);
        })->when($filters['supplier_id'] ?? null, function ($query, $supplierId) {
            $query->where('supplier_id', $supplierId);
        })->when($filters['orderByField'] ?? null, function ($query, $orderByField) use ($filters) {
            $orderBy = $filters['orderBy'] ?? 'desc';
            $query->orderBy($orderByField, $orderBy);
        }, function ($query) {
            $query->orderBy('bill_date', 'desc');
        });
    }

    /**
     * Scope: Paginate data
     */
    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    /**
     * Mark bill as sent
     */
    public function markAsSent()
    {
        $this->status = self::STATUS_SENT;
        $this->sent = true;
        $this->save();
    }

    /**
     * Mark bill as viewed
     */
    public function markAsViewed()
    {
        if ($this->status == self::STATUS_SENT) {
            $this->status = self::STATUS_VIEWED;
            $this->viewed = true;
            $this->save();
        }
    }

    /**
     * Mark bill as completed
     */
    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    /**
     * Update paid status based on payments
     */
    public function updatePaidStatus()
    {
        $paidAmount = $this->payments()->sum('amount');
        $dueAmount = $this->total - $paidAmount;

        if ($dueAmount <= 0) {
            $this->paid_status = self::PAID_STATUS_PAID;
            $this->status = self::STATUS_COMPLETED;
        } elseif ($paidAmount > 0) {
            $this->paid_status = self::PAID_STATUS_PARTIALLY_PAID;
        } else {
            $this->paid_status = self::PAID_STATUS_UNPAID;
        }

        $this->save();
    }

    /**
     * Check if bill is in a locked tax period
     */
    public function isInLockedPeriod(): bool
    {
        if (!$this->taxReportPeriod) {
            return false;
        }

        return $this->taxReportPeriod->is_locked ?? false;
    }

    /**
     * Delete bills and their related records
     */
    public static function deleteBills($ids)
    {
        foreach ($ids as $id) {
            $bill = self::find($id);

            if ($bill->items()->exists()) {
                $bill->items()->delete();
            }

            if ($bill->payments()->exists()) {
                $bill->payments()->delete();
            }

            if ($bill->taxes()->exists()) {
                $bill->taxes()->delete();
            }

            $bill->delete();
        }

        return true;
    }
}

// CLAUDE-CHECKPOINT
