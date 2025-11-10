<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

/**
 * Bank Consent Model
 *
 * Represents OAuth2/PSD2 consent for accessing bank account information
 * Manages encrypted access tokens and consent lifecycle
 *
 * @property int $id
 * @property int $bank_connection_id
 * @property string $consent_id Provider-specific consent identifier
 * @property string $status Consent status (pending, active, expired, revoked)
 * @property string $scope Consent scope (accounts, balances, transactions)
 * @property string|null $access_token Encrypted OAuth2 access token
 * @property string|null $refresh_token Encrypted OAuth2 refresh token
 * @property string $token_type Token type (usually 'Bearer')
 * @property \Carbon\Carbon|null $granted_at When consent was granted
 * @property \Carbon\Carbon|null $expires_at When consent expires
 * @property \Carbon\Carbon|null $last_used_at Last time consent was used
 * @property array|null $metadata Additional consent metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BankConsent extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    // Scope constants - PSD2 service types
    public const SCOPE_ACCOUNTS = 'accounts';
    public const SCOPE_BALANCES = 'balances';
    public const SCOPE_TRANSACTIONS = 'transactions';

    protected $fillable = [
        'bank_connection_id',
        'consent_id',
        'status',
        'scope',
        'access_token',
        'refresh_token',
        'token_type',
        'granted_at',
        'expires_at',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Get the bank connection this consent belongs to
     */
    public function bankConnection(): BelongsTo
    {
        return $this->belongsTo(BankConnection::class);
    }

    /**
     * Encrypt access token when storing
     * Decrypt when retrieving
     */
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Encrypt refresh token when storing
     * Decrypt when retrieving
     */
    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Activate the consent
     */
    public function activate(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'granted_at' => $this->granted_at ?? now(),
        ]);
    }

    /**
     * Expire the consent
     */
    public function expire(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Revoke the consent
     */
    public function revoke(): bool
    {
        return $this->update([
            'status' => self::STATUS_REVOKED,
        ]);
    }

    /**
     * Check if consent is valid (active and not expired)
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if consent is expired
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Update last used timestamp
     */
    public function markAsUsed(): bool
    {
        return $this->update([
            'last_used_at' => now(),
        ]);
    }

    /**
     * Check if consent is expiring soon
     *
     * @param int $hoursBuffer Hours before expiration to consider consent as expiring
     * @return bool True if consent expires within the buffer period
     */
    public function isExpiringSoon(int $hoursBuffer = 24): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->subHours($hoursBuffer)->isPast();
    }

    /**
     * Get remaining hours until expiration
     *
     * @return int|null Hours until expiration (null if no expiry set)
     */
    public function getHoursUntilExpiration(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        $hours = now()->diffInHours($this->expires_at, false);
        return $hours > 0 ? $hours : 0;
    }

    /**
     * Scope: Get only active consents
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Get non-expired consents
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Get valid consents (active and not expired)
     */
    public function scopeValid($query)
    {
        return $query->active()->notExpired();
    }

    /**
     * Scope: Get consents for a specific scope
     */
    public function scopeForScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope: Get consents for a specific connection
     */
    public function scopeForConnection($query, int $connectionId)
    {
        return $query->where('bank_connection_id', $connectionId);
    }

    /**
     * Scope: Get consents expiring soon
     */
    public function scopeExpiringSoon($query, int $hoursBuffer = 24)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addHours($hoursBuffer))
            ->where('expires_at', '>', now());
    }
}

// CLAUDE-CHECKPOINT
