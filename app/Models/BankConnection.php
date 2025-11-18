<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bank Connection Model
 *
 * Represents a company's connection to a PSD2 bank provider
 * Manages connection lifecycle and consent relationships
 *
 * @property int $id
 * @property int $company_id
 * @property int $bank_provider_id
 * @property int|null $created_by User who created the connection
 * @property string $status Connection status (pending, active, expired, revoked, error)
 * @property string|null $external_id Provider-specific connection ID
 * @property string|null $institution_id Provider-specific institution ID
 * @property \Carbon\Carbon|null $connected_at When connection became active
 * @property \Carbon\Carbon|null $expires_at When connection expires
 * @property \Carbon\Carbon|null $last_synced_at Last successful sync timestamp
 * @property array|null $metadata Additional connection metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BankConnection extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'company_id',
        'bank_provider_id',
        'created_by',
        'status',
        'external_id',
        'institution_id',
        'connected_at',
        'expires_at',
        'last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Get the company that owns this connection
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the bank provider for this connection
     */
    public function bankProvider(): BelongsTo
    {
        return $this->belongsTo(BankProvider::class);
    }

    /**
     * Get all consents for this connection
     */
    public function consents(): HasMany
    {
        return $this->hasMany(BankConsent::class);
    }

    /**
     * Get the user who created this connection
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Activate the connection
     */
    public function activate(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'connected_at' => now(),
        ]);
    }

    /**
     * Expire the connection
     */
    public function expire(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Revoke the connection
     */
    public function revoke(): bool
    {
        return $this->update([
            'status' => self::STATUS_REVOKED,
        ]);
    }

    /**
     * Update last sync timestamp
     */
    public function sync(): bool
    {
        return $this->update([
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Check if connection is expired
     */
    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if connection is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && ! $this->isExpired();
    }

    /**
     * Scope: Filter by company
     */
    public function scopeWhereCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Get only active connections
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Get expired connections
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
            ->orWhere(function ($q) {
                $q->where('status', self::STATUS_ACTIVE)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
            });
    }

    /**
     * Scope: Get connections for a specific bank provider
     */
    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('bank_provider_id', $providerId);
    }

    /**
     * Scope: Get connections that need syncing
     */
    public function scopeNeedsSync($query, int $hours = 24)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($hours) {
                $q->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<=', now()->subHours($hours));
            });
    }
}

// CLAUDE-CHECKPOINT
