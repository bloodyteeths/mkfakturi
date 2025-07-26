<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_name',
        'tax_id',
        'registration_number', 
        'bank_account',
        'bank_name',
        'commission_rate',
        'is_active',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2'
    ];

    /**
     * Get the user associated with the partner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the commissions for the partner
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Scope a query to only include active partners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}