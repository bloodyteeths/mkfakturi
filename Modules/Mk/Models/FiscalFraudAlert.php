<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\FiscalDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalFraudAlert extends Model
{
    protected $fillable = [
        'company_id',
        'fiscal_device_id',
        'user_id',
        'alert_type',
        'severity',
        'description',
        'evidence',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'evidence' => 'array',
        'resolved_at' => 'datetime',
    ];

    // Alert types
    const TYPE_UNEXPECTED_CLOSE = 'unexpected_close';
    const TYPE_OFF_HOURS = 'off_hours_activity';
    const TYPE_GAP_IN_RECEIPTS = 'gap_in_receipts';
    const TYPE_CASH_DISCREPANCY = 'cash_discrepancy';
    const TYPE_FREQUENT_VOIDS = 'frequent_voids';
    const TYPE_NO_Z_REPORT = 'no_z_report';
    const TYPE_RAPID_OPEN_CLOSE = 'rapid_open_close';

    // Statuses
    const STATUS_OPEN = 'open';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_INVESTIGATED = 'investigated';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_FALSE_POSITIVE = 'false_positive';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fiscalDevice(): BelongsTo
    {
        return $this->belongsTo(FiscalDevice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_FALSE_POSITIVE]);
    }
}

// CLAUDE-CHECKPOINT
