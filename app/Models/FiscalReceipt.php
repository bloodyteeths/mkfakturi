<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fiscal Receipt Model
 *
 * Stores fiscalized receipt data returned by fiscal devices.
 *
 * @property int $id
 * @property int $company_id
 * @property int $fiscal_device_id
 * @property int|null $invoice_id
 * @property string $receipt_number
 * @property int $amount
 * @property int $vat_amount
 * @property string $fiscal_id
 * @property string|null $raw_response
 * @property array|null $metadata
 */
class FiscalReceipt extends Model
{
    protected $fillable = [
        'company_id',
        'fiscal_device_id',
        'invoice_id',
        'receipt_number',
        'amount',
        'vat_amount',
        'fiscal_id',
        'raw_response',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'vat_amount' => 'integer',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fiscalDevice(): BelongsTo
    {
        return $this->belongsTo(FiscalDevice::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForDevice($query, int $deviceId)
    {
        return $query->where('fiscal_device_id', $deviceId);
    }
}
// CLAUDE-CHECKPOINT
