<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomReportTemplate extends Model
{
    protected $table = 'custom_report_templates';

    protected $fillable = [
        'company_id',
        'name',
        'account_filter',
        'columns',
        'period_type',
        'group_by',
        'comparison',
        'schedule_cron',
        'schedule_emails',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'account_filter' => 'array',
            'columns' => 'array',
            'schedule_emails' => 'array',
            'created_by' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---- Scopes ----

    /**
     * Scope to a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('custom_report_templates.company_id', $companyId);
    }
}

// CLAUDE-CHECKPOINT
