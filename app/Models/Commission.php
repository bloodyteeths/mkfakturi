<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'company_id',
        'invoice_id',
        'payment_id',
        'commission_type',
        'base_amount',
        'commission_rate',
        'commission_amount',
        'currency_id',
        'status',
        'period_start',
        'period_end',
        'paid_date',
        'payment_reference',
        'notes',
        'creator_id'
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_date' => 'date'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_INVOICE = 'invoice';
    const TYPE_PAYMENT = 'payment';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_CUSTOM = 'custom';

    /**
     * Get the partner that owns the commission
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the company that owns the commission
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the invoice associated with the commission
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment associated with the commission
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the currency of the commission
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the creator of the commission
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Scope a query to only include pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include approved commissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope a query to only include paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Calculate commission amount based on base amount and rate
     */
    public function calculateCommission()
    {
        $this->commission_amount = $this->base_amount * ($this->commission_rate / 100);
        return $this->commission_amount;
    }
}