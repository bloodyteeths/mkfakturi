<?php

namespace Modules\Mk\Bitrix\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UnsubscribeToken Model
 *
 * Represents a one-time token for email unsubscription.
 * Used to generate secure unsubscribe links in outreach emails.
 *
 * @property int $id
 * @property string $token
 * @property string $email
 * @property int|null $outreach_lead_id
 * @property bool $used
 * @property \Carbon\Carbon|null $used_at
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class UnsubscribeToken extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unsubscribe_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'token',
        'email',
        'outreach_lead_id',
        'used',
        'used_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'used' => 'boolean',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Token expiry in days.
     */
    public const TOKEN_EXPIRY_DAYS = 365;

    /**
     * Get the outreach lead this token belongs to.
     *
     * @return BelongsTo
     */
    public function outreachLead(): BelongsTo
    {
        return $this->belongsTo(OutreachLead::class, 'outreach_lead_id');
    }

    /**
     * Generate a new token for an email address.
     *
     * @param string $email
     * @param int|null $leadId
     * @return static
     */
    public static function generate(string $email, ?int $leadId = null): static
    {
        return static::create([
            'token' => Str::random(64),
            'email' => strtolower(trim($email)),
            'outreach_lead_id' => $leadId,
            'used' => false,
            'expires_at' => now()->addDays(self::TOKEN_EXPIRY_DAYS),
        ]);
    }

    /**
     * Generate a new token for a lead.
     *
     * @param int $leadId
     * @return static
     */
    public static function generateForLead(int $leadId): static
    {
        $lead = OutreachLead::find($leadId);
        if (!$lead) {
            throw new \InvalidArgumentException("Lead with ID {$leadId} not found");
        }

        return static::generate($lead->email, $leadId);
    }

    /**
     * Find a token by its value.
     *
     * @param string $token
     * @return static|null
     */
    public static function findByToken(string $token): ?static
    {
        return static::where('token', $token)->first();
    }

    /**
     * Find a valid (unused and not expired) token.
     *
     * @param string $token
     * @return static|null
     */
    public static function findValidToken(string $token): ?static
    {
        return static::where('token', $token)
            ->where('used', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Check if this token is valid (unused and not expired).
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->used) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Mark this token as used.
     *
     * @return bool
     */
    public function markUsed(): bool
    {
        return $this->update([
            'used' => true,
            'used_at' => now(),
        ]);
    }

    /**
     * Get the unsubscribe URL for this token.
     *
     * @return string
     */
    public function getUnsubscribeUrl(): string
    {
        return url('/unsubscribe/' . $this->token);
    }

    /**
     * Scope a query to only include valid tokens.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope a query to only include used tokens.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUsed($query)
    {
        return $query->where('used', true);
    }

    /**
     * Scope a query to only include expired tokens.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Get or create a valid token for an email.
     *
     * @param string $email
     * @param int|null $leadId
     * @return static
     */
    public static function getOrCreateForEmail(string $email, ?int $leadId = null): static
    {
        $token = static::where('email', strtolower(trim($email)))
            ->valid()
            ->first();

        if ($token) {
            return $token;
        }

        return static::generate($email, $leadId);
    }

    /**
     * Get or create a valid token for a lead.
     *
     * @param int $leadId
     * @return static
     */
    public static function getOrCreateForLead(int $leadId): static
    {
        $token = static::where('outreach_lead_id', $leadId)
            ->valid()
            ->first();

        if ($token) {
            return $token;
        }

        return static::generateForLead($leadId);
    }
}

// CLAUDE-CHECKPOINT
