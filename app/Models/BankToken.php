<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Bank Token Model
 *
 * Stores encrypted OAuth2 tokens for PSD2 banking integration
 * Supports automatic token refresh and expiration tracking
 *
 * @property int $id
 * @property int $company_id
 * @property string $bank_code Bank identifier (stopanska, nlb, komercijalna)
 * @property string $access_token Encrypted OAuth access token
 * @property string|null $refresh_token Encrypted OAuth refresh token
 * @property string $token_type Token type (usually 'Bearer')
 * @property \Carbon\Carbon $expires_at Token expiration timestamp
 * @property string|null $scope OAuth scope
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BankToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'bank_code',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_at',
        'scope',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the company that owns the bank token
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
     * Check if the token is expiring soon
     *
     * @param  int  $minutesBuffer  Minutes before expiration to consider token as expiring
     * @return bool True if token expires within the buffer period
     */
    public function isExpiringSoon(int $minutesBuffer = 5): bool
    {
        if (! $this->expires_at) {
            return true; // No expiry = assume expired for safety
        }

        return $this->expires_at->subMinutes($minutesBuffer)->isPast();
    }

    /**
     * Check if the token is expired
     *
     * @return bool True if token is already expired
     */
    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return true; // No expiry = assume expired for safety
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the token is valid (not expired and not expiring soon)
     *
     * @param  int  $minutesBuffer  Minutes before expiration to consider token as expiring
     * @return bool True if token is still valid
     */
    public function isValid(int $minutesBuffer = 5): bool
    {
        return ! $this->isExpiringSoon($minutesBuffer);
    }

    /**
     * Get remaining minutes until expiration
     *
     * @return int Minutes until expiration (negative if already expired)
     */
    public function getMinutesUntilExpiration(): int
    {
        if (! $this->expires_at) {
            return -1;
        }

        return now()->diffInMinutes($this->expires_at, false);
    }

    /**
     * Scope: Get tokens for a specific bank
     */
    public function scopeForBank($query, string $bankCode)
    {
        return $query->where('bank_code', $bankCode);
    }

    /**
     * Scope: Get tokens for a specific company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Get valid (non-expired) tokens
     */
    public function scopeValid($query, int $minutesBuffer = 5)
    {
        return $query->where('expires_at', '>', now()->addMinutes($minutesBuffer));
    }

    /**
     * Scope: Get expiring or expired tokens that need refresh
     */
    public function scopeNeedsRefresh($query, int $minutesBuffer = 5)
    {
        return $query->where('expires_at', '<=', now()->addMinutes($minutesBuffer));
    }
}

// CLAUDE-CHECKPOINT
