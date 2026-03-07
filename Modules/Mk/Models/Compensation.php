<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Compensation extends Model
{
    use SoftDeletes;

    protected $table = 'compensations';

    protected $fillable = [
        'company_id',
        'compensation_number',
        'compensation_date',
        'counterparty_type',
        'customer_id',
        'supplier_id',
        'type',
        'status',
        'total_amount',
        'currency_id',
        'notes',
        'receivables_total',
        'payables_total',
        'receivables_remaining',
        'payables_remaining',
        'ifrs_transaction_id',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'compensation_date' => 'date',
            'confirmed_at' => 'datetime',
            'total_amount' => 'integer',
            'receivables_total' => 'integer',
            'payables_total' => 'integer',
            'receivables_remaining' => 'integer',
            'payables_remaining' => 'integer',
        ];
    }

    protected $appends = [
        'formattedTotal',
    ];

    /**
     * Boot method: auto-generate compensation_number on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Compensation $compensation) {
            if (empty($compensation->compensation_number)) {
                $year = date('Y');
                $sequence = static::where('company_id', $compensation->company_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $compensation->compensation_number = sprintf('KOMP-%d-%04d', $year, $sequence);
            }
        });
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompensationItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // ---- Scopes ----

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('compensations.company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('compensations.status', $status);
    }

    // ---- Accessors ----

    /**
     * Get the total_amount formatted as a decimal (divided by 100).
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount / 100, 2, '.', ',');
    }

    /**
     * Get counterparty display name.
     */
    public function getCounterpartyNameAttribute(): string
    {
        if ($this->counterparty_type === 'customer') {
            return $this->customer?->name ?? '';
        }

        if ($this->counterparty_type === 'supplier') {
            return $this->supplier?->name ?? '';
        }

        // 'both' — prefer customer name, fallback to supplier
        return $this->customer?->name ?? $this->supplier?->name ?? '';
    }
}

// CLAUDE-CHECKPOINT
