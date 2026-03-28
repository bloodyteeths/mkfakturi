<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
 * @property int|null $operator_id
 * @property string|null $operator_name
 * @property string|null $unique_sale_number
 * @property string|null $payment_type
 * @property array|null $tax_breakdown
 * @property bool $is_storno
 * @property int|null $storno_of_receipt_id
 * @property \Illuminate\Support\Carbon|null $device_receipt_datetime
 * @property array|null $items_snapshot
 * @property string|null $device_registration_number
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
        'source',
        'operator_id',
        'operator_name',
        'unique_sale_number',
        'payment_type',
        'tax_breakdown',
        'is_storno',
        'storno_of_receipt_id',
        'device_receipt_datetime',
        'items_snapshot',
        'device_registration_number',
    ];

    protected $casts = [
        'amount' => 'integer',
        'vat_amount' => 'integer',
        'metadata' => 'array',
        'tax_breakdown' => 'array',
        'items_snapshot' => 'array',
        'is_storno' => 'boolean',
        'device_receipt_datetime' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fiscalDevice(): BelongsTo
    {
        return $this->belongsTo(FiscalDevice::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function stornoOfReceipt(): BelongsTo
    {
        return $this->belongsTo(FiscalReceipt::class, 'storno_of_receipt_id');
    }

    public function stornoReceipt(): HasOne
    {
        return $this->hasOne(FiscalReceipt::class, 'storno_of_receipt_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForDevice($query, int $deviceId)
    {
        return $query->where('fiscal_device_id', $deviceId);
    }

    public function scopeStornos($query)
    {
        return $query->where('is_storno', true);
    }

    public function scopeNonStornos($query)
    {
        return $query->where('is_storno', false);
    }
}

// CLAUDE-CHECKPOINT
