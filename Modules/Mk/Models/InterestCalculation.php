<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterestCalculation extends Model
{
    protected $table = 'interest_calculations';

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'calculation_date',
        'principal_amount',
        'days_overdue',
        'annual_rate',
        'interest_amount',
        'status',
        'interest_invoice_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'calculation_date' => 'date',
            'principal_amount' => 'integer',
            'days_overdue' => 'integer',
            'annual_rate' => 'decimal:2',
            'interest_amount' => 'integer',
        ];
    }

    protected $appends = [
        'formattedPrincipal',
        'formattedInterest',
    ];

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function interestInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'interest_invoice_id');
    }

    // ---- Scopes ----

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('interest_calculations.company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('interest_calculations.status', $status);
    }

    // ---- Accessors ----

    /**
     * Get the principal_amount formatted as a decimal (divided by 100).
     */
    public function getFormattedPrincipalAttribute(): string
    {
        return number_format($this->principal_amount / 100, 2, '.', ',');
    }

    /**
     * Get the interest_amount formatted as a decimal (divided by 100).
     */
    public function getFormattedInterestAttribute(): string
    {
        return number_format($this->interest_amount / 100, 2, '.', ',');
    }
}

// CLAUDE-CHECKPOINT
