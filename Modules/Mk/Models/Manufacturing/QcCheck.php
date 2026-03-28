<?php

namespace Modules\Mk\Models\Manufacturing;

use App\Models\Company;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcCheck extends Model
{
    use BelongsToCompany;

    protected $table = 'production_qc_checks';

    const RESULT_PASS = 'pass';
    const RESULT_FAIL = 'fail';
    const RESULT_CONDITIONAL = 'conditional';

    const DISPOSITION_NONE = 'none';
    const DISPOSITION_REWORK = 'rework';
    const DISPOSITION_SCRAP = 'scrap';

    protected $fillable = [
        'production_order_id',
        'company_id',
        'inspector_id',
        'check_date',
        'result',
        'quantity_inspected',
        'quantity_passed',
        'quantity_rejected',
        'notes',
        'checklist',
        'defects',
        'disposition',
        'rework_order_id',
        'scrap_quantity',
    ];

    protected function casts(): array
    {
        return [
            'check_date' => 'date',
            'quantity_inspected' => 'decimal:4',
            'quantity_passed' => 'decimal:4',
            'quantity_rejected' => 'decimal:4',
            'checklist' => 'array',
            'defects' => 'array',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function reworkOrder(): BelongsTo
    {
        return $this->belongsTo(\Modules\Mk\Models\Manufacturing\ProductionOrder::class, 'rework_order_id');
    }

    public function isPassed(): bool
    {
        return $this->result === self::RESULT_PASS;
    }

    public function isFailed(): bool
    {
        return $this->result === self::RESULT_FAIL;
    }

    /**
     * Quality rate as percentage.
     */
    public function qualityRate(): float
    {
        if ($this->quantity_inspected <= 0) {
            return 100;
        }

        return round(($this->quantity_passed / $this->quantity_inspected) * 100, 1);
    }
}
