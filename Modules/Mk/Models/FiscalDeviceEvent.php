<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\FiscalDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalDeviceEvent extends Model
{
    protected $fillable = [
        'company_id',
        'fiscal_device_id',
        'user_id',
        'event_type',
        'source',
        'cash_amount',
        'notes',
        'metadata',
        'event_at',
    ];

    protected $casts = [
        'cash_amount' => 'integer',
        'metadata' => 'array',
        'event_at' => 'datetime',
    ];

    // Event type constants
    const TYPE_OPEN = 'open';
    const TYPE_CLOSE = 'close';
    const TYPE_Z_REPORT = 'z_report';
    const TYPE_ERROR = 'error';
    const TYPE_RECEIPT = 'receipt';
    const TYPE_VOID = 'void';
    const TYPE_STATUS_CHECK = 'status_check';

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

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForDevice($query, int $deviceId)
    {
        return $query->where('fiscal_device_id', $deviceId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('event_at', [$start, $end]);
    }
}

// CLAUDE-CHECKPOINT
