<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class BillPayment extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [
        'id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'payment_date',
    ];

    protected $appends = [
        'formattedPaymentDate',
        'formattedCreatedAt',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'exchange_rate' => 'float',
            'base_amount' => 'integer',
            'posted_to_ifrs' => 'boolean',
        ];
    }

    /**
     * Get formatted payment date
     */
    public function getFormattedPaymentDateAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        return Carbon::parse($this->payment_date)->translatedFormat($dateFormat);
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
     * Relationship: BillPayment belongs to Bill
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Relationship: BillPayment belongs to Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship: BillPayment was created by User
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Relationship: BillPayment belongs to PaymentMethod
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the supplier through the bill
     */
    public function supplier()
    {
        return $this->bill ? $this->bill->supplier : null;
    }

    /**
     * Mark payment as completed and update bill status
     */
    public function markAsCompleted()
    {
        if ($this->bill) {
            $this->bill->updatePaidStatus();
        }
    }

    /**
     * Scope: Filter by company
     */
    public function scopeWhereCompany($query)
    {
        $query->where('bill_payments.company_id', request()->header('company'));
    }

    /**
     * Scope: Filter by bill
     */
    public function scopeWhereBill($query, $billId)
    {
        return $query->where('bill_payments.bill_id', $billId);
    }

    /**
     * Scope: Filter by payment date range
     */
    public function scopePaymentsBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'bill_payments.payment_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    /**
     * Scope: Search across multiple fields
     */
    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->where(function ($query) use ($term) {
                $query->where('payment_number', 'LIKE', '%'.$term.'%')
                    ->orWhereHas('bill', function ($query) use ($term) {
                        $query->where('bill_number', 'LIKE', '%'.$term.'%');
                    });
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
        })->when($filters['bill_id'] ?? null, function ($query, $billId) {
            $query->whereBill($billId);
        })->when(($filters['from_date'] ?? null) && ($filters['to_date'] ?? null), function ($query) use ($filters) {
            $start = Carbon::parse($filters['from_date']);
            $end = Carbon::parse($filters['to_date']);
            $query->paymentsBetween($start, $end);
        })->when($filters['orderByField'] ?? null, function ($query, $orderByField) use ($filters) {
            $orderBy = $filters['orderBy'] ?? 'desc';
            $query->orderBy($orderByField, $orderBy);
        }, function ($query) {
            $query->orderBy('payment_date', 'desc');
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
}

// CLAUDE-CHECKPOINT
