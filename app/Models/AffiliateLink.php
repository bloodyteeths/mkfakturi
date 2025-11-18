<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AffiliateLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'code',
        'target',
        'description',
        'is_active',
        'clicks',
        'conversions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'clicks' => 'integer',
        'conversions' => 'integer',
    ];

    /**
     * Get the partner that owns this affiliate link
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Generate a unique referral code for a partner
     */
    public static function generateUniqueCode(Partner $partner, ?string $customCode = null): string
    {
        if ($customCode) {
            // Validate custom code is available
            if (! self::where('code', $customCode)->exists()) {
                return $customCode;
            }
        }

        // Generate code from partner name
        $baseName = Str::slug(Str::limit($partner->name, 20, ''));
        $code = strtoupper($baseName);

        // Ensure uniqueness
        $counter = 1;
        while (self::where('code', $code)->exists()) {
            $code = strtoupper($baseName.'_'.$counter);
            $counter++;
        }

        return $code;
    }

    /**
     * Get the full referral URL for this affiliate link
     */
    public function getUrl(?string $path = null): string
    {
        $baseUrl = config('app.url');
        $path = $path ?? '/register';
        $refParam = config('affiliate.ref_param', 'ref');

        return $baseUrl.$path.'?'.$refParam.'='.$this->code;
    }

    /**
     * Get conversion rate percentage
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->clicks == 0) {
            return 0;
        }

        return round(($this->conversions / $this->clicks) * 100, 2);
    }

    /**
     * Scope a query to only include active links
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Increment conversions when a signup is completed
     */
    public function recordConversion(): void
    {
        $this->increment('conversions');
    }
}

// CLAUDE-CHECKPOINT
