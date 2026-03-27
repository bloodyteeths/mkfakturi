<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelOrder extends Model
{
    use SoftDeletes;

    protected $table = 'travel_orders';

    protected $fillable = [
        'company_id',
        'employee_id',
        'travel_number',
        'type',
        'transport_type_category',
        'transport_mode',
        'purpose',
        'departure_date',
        'return_date',
        'status',
        'advance_amount',
        'total_per_diem',
        'total_expenses',
        'total_mileage_cost',
        'total_km',
        'total_fuel_consumed',
        'total_fuel_cost',
        'total_toll_cost',
        'total_forwarding_cost',
        'grand_total',
        'reimbursement_amount',
        'cost_center_id',
        'ifrs_transaction_id',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'datetime',
            'return_date' => 'datetime',
            'advance_amount' => 'integer',
            'total_per_diem' => 'integer',
            'total_expenses' => 'integer',
            'total_mileage_cost' => 'integer',
            'total_km' => 'integer',
            'total_fuel_consumed' => 'decimal:2',
            'total_fuel_cost' => 'integer',
            'total_toll_cost' => 'integer',
            'total_forwarding_cost' => 'integer',
            'grand_total' => 'integer',
            'reimbursement_amount' => 'integer',
        ];
    }

    protected $appends = [
        'formattedGrandTotal',
        'formattedReimbursement',
    ];

    /**
     * Boot method: auto-generate travel_number on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TravelOrder $order) {
            if (empty($order->travel_number)) {
                $year = date('Y');
                $sequence = static::where('company_id', $order->company_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $order->travel_number = sprintf('PN-%d-%04d', $year, $sequence);
            }
        });
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PayrollEmployee::class, 'employee_id');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(TravelSegment::class, 'travel_order_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(TravelExpense::class, 'travel_order_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(TravelOrderVehicle::class, 'travel_order_id');
    }

    public function crew(): HasMany
    {
        return $this->hasMany(TravelOrderCrew::class, 'travel_order_id');
    }

    public function cargo(): HasMany
    {
        return $this->hasMany(TravelOrderCargo::class, 'travel_order_id');
    }

    // ---- Scopes ----

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('travel_orders.company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('travel_orders.status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('travel_orders.type', $type);
    }

    // ---- Accessors ----

    public function getFormattedGrandTotalAttribute(): string
    {
        return number_format($this->grand_total / 100, 2, '.', ',');
    }

    public function getFormattedReimbursementAttribute(): string
    {
        return number_format($this->reimbursement_amount / 100, 2, '.', ',');
    }
}

// CLAUDE-CHECKPOINT
