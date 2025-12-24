<?php

namespace App\Models;

use App\Traits\HasAuditing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollEmployee extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditing;

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'embg',
        'bank_account_iban',
        'bank_name',
        'employment_date',
        'termination_date',
        'employment_type',
        'department',
        'position',
        'base_salary_amount',
        'currency_id',
        'is_active',
        'creator_id',
    ];

    protected $dates = [
        'employment_date',
        'termination_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'base_salary_amount' => 'integer',
            'is_active' => 'boolean',
            'employment_date' => 'date',
            'termination_date' => 'date',
        ];
    }

    /**
     * Get the company that owns the employee.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user associated with the employee (if any).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the salary structures for the employee.
     */
    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class, 'employee_id');
    }

    /**
     * Get the current active salary structure.
     */
    public function currentSalaryStructure()
    {
        return $this->hasOne(SalaryStructure::class, 'employee_id')
            ->where('is_current', true);
    }

    /**
     * Get the payroll run lines for the employee.
     */
    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollRunLine::class, 'employee_id');
    }

    /**
     * Get the user who created this employee record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the currency for the employee's salary.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Scope to get only active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}

// LLM-CHECKPOINT
