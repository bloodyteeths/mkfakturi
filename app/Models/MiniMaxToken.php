<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class MiniMaxToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'token_name',
        'api_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to Company model
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Encrypt token before saving to database
     */
    public function setApiTokenAttribute($value): void
    {
        if ($value) {
            $this->attributes['api_token'] = Crypt::encrypt($value);
        }
    }

    /**
     * Decrypt token when retrieving from database
     */
    public function getApiTokenAttribute($value): ?string
    {
        if ($value) {
            return Crypt::decrypt($value);
        }

        return null;
    }

    /**
     * Get the raw encrypted token (for internal use)
     */
    public function getRawApiTokenAttribute(): ?string
    {
        return $this->attributes['api_token'] ?? null;
    }

    /**
     * Scope for filtering active tokens
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for filtering by company
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Check if token is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Activate the token
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the token
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }
}
