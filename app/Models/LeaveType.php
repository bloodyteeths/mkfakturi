<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Leave Type Model
 *
 * Represents configurable leave types per company.
 * Macedonian labor law defaults:
 * - ANNUAL: 20 days at 100% pay
 * - SICK: 30 days at 70% pay (employer-funded)
 * - MATERNITY: 270 days (9 months) at 100% pay
 * - UNPAID: 30 days at 0% pay
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $name_mk
 * @property string $code
 * @property int $max_days_per_year
 * @property float $pay_percentage
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class LeaveType extends Model
{
    use HasFactory;

    /** @var string Leave type code for annual leave */
    public const CODE_ANNUAL = 'ANNUAL';

    /** @var string Leave type code for sick leave (70% employer-funded, first 30 days) */
    public const CODE_SICK = 'SICK';

    /** @var string Leave type code for sick leave due to work injury (100% from day 1) */
    public const CODE_SICK_WORK_INJURY = 'SICK_WORK_INJURY';

    /** @var string Leave type code for maternity leave */
    public const CODE_MATERNITY = 'MATERNITY';

    /** @var string Leave type code for parental leave (father) */
    public const CODE_PARENTAL = 'PARENTAL';

    /** @var string Leave type code for unpaid leave */
    public const CODE_UNPAID = 'UNPAID';

    /** @var string Leave type code for marriage leave (Art. 146) */
    public const CODE_MARRIAGE = 'MARRIAGE';

    /** @var string Leave type code for bereavement leave (Art. 146) */
    public const CODE_BEREAVEMENT = 'BEREAVEMENT';

    /** @var string Leave type code for blood donation leave */
    public const CODE_BLOOD_DONATION = 'BLOOD_DONATION';

    /** @var string Leave type code for study/exam leave */
    public const CODE_STUDY = 'STUDY';

    /** @var string Leave type code for moving house */
    public const CODE_MOVING = 'MOVING';

    /** @var string Leave type code for natural disaster leave */
    public const CODE_NATURAL_DISASTER = 'NATURAL_DISASTER';

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'name_mk',
        'code',
        'max_days_per_year',
        'pay_percentage',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_days_per_year' => 'integer',
            'pay_percentage' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the company that owns this leave type.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the leave requests for this leave type.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Scope to get only active leave types.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by company.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}

