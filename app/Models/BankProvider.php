<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bank Provider Model
 *
 * Represents supported PSD2 banking providers for Macedonia
 * Stores provider configuration and capabilities
 *
 * @property int $id
 * @property string $code Unique provider code (nlb, stopanska, komercijalna)
 * @property string $name Display name
 * @property string $bic Bank Identification Code
 * @property string $country ISO country code
 * @property string $environment Sandbox or production
 * @property string $base_url API base URL
 * @property bool $supports_ais Account Information Service support
 * @property bool $supports_pis Payment Initiation Service support
 * @property bool $is_active Provider is enabled
 * @property array|null $metadata Additional provider-specific settings
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BankProvider extends Model
{
    use HasFactory;

    // Bank provider constants
    public const NLB = 'nlb';
    public const STOPANSKA = 'stopanska';
    public const KOMERCIJALNA = 'komercijalna';

    // Environment constants
    public const SANDBOX = 'sandbox';
    public const PRODUCTION = 'production';

    protected $fillable = [
        'code',
        'name',
        'bic',
        'country',
        'environment',
        'base_url',
        'supports_ais',
        'supports_pis',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'supports_ais' => 'boolean',
        'supports_pis' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    /**
     * Get all connections for this provider
     */
    public function connections(): HasMany
    {
        return $this->hasMany(BankConnection::class);
    }

    /**
     * Scope: Get only active providers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get providers supporting AIS (Account Information Service)
     */
    public function scopeSupportingAis($query)
    {
        return $query->where('supports_ais', true);
    }

    /**
     * Scope: Get providers supporting PIS (Payment Initiation Service)
     */
    public function scopeSupportingPis($query)
    {
        return $query->where('supports_pis', true);
    }

    /**
     * Check if provider supports AIS
     */
    public function supportsAis(): bool
    {
        return $this->supports_ais;
    }

    /**
     * Check if provider supports PIS
     */
    public function supportsPis(): bool
    {
        return $this->supports_pis;
    }

    /**
     * Check if provider is in production environment
     */
    public function isProduction(): bool
    {
        return $this->environment === self::PRODUCTION;
    }

    /**
     * Check if provider is in sandbox environment
     */
    public function isSandbox(): bool
    {
        return $this->environment === self::SANDBOX;
    }
}

// CLAUDE-CHECKPOINT
