<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fiscal Device Model
 *
 * Represents a registered fiscal device (printer or cash register) for a company.
 * Covers all Macedonian fiscal devices sold by Fiditek: Daisy FX, Давид,
 * Развигорец, Северец, Expert SX, Пелистерец, Alpha.
 *
 * @property int $id
 * @property int $company_id
 * @property string $device_type
 * @property string|null $name
 * @property string $serial_number
 * @property string|null $ip_address
 * @property int|null $port
 * @property string $connection_type
 * @property string|null $serial_port
 * @property bool $is_active
 * @property array|null $metadata
 */
class FiscalDevice extends Model
{
    protected $fillable = [
        'company_id',
        'device_type',
        'name',
        'serial_number',
        'ip_address',
        'port',
        'connection_type',
        'serial_port',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(FiscalReceipt::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('device_type', strtolower($type));
    }

    /**
     * Get connection config array for the driver's connect() method.
     */
    public function getConnectionConfig(): array
    {
        return [
            'connection_type' => $this->connection_type ?? 'tcp',
            'ip_address' => $this->ip_address,
            'port' => $this->port,
            'serial_port' => $this->serial_port,
            'serial_number' => $this->serial_number,
        ];
    }
}
