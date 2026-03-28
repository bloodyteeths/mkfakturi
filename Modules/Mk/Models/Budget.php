<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;

    protected $table = 'budgets';

    protected $fillable = [
        'company_id',
        'number',
        'name',
        'period_type',
        'start_date',
        'end_date',
        'status',
        'cost_center_id',
        'scenario',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'cost_center_id' => 'integer',
            'created_by' => 'integer',
            'approved_by' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ---- Scopes ----

    /**
     * Scope to a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('budgets.company_id', $companyId);
    }

    /**
     * Scope by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('budgets.status', $status);
    }

    /**
     * Scope by cost center.
     */
    public function scopeByCostCenter($query, int $costCenterId)
    {
        return $query->where('budgets.cost_center_id', $costCenterId);
    }
}

// CLAUDE-CHECKPOINT
