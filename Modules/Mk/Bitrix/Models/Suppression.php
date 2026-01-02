<?php

namespace Modules\Mk\Bitrix\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Suppression Model
 *
 * Represents an email address on the suppression list.
 * Emails on this list will not receive outreach emails.
 *
 * @property int $id
 * @property string $email
 * @property string $type
 * @property string|null $reason
 * @property string $source
 * @property array|null $meta
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Suppression extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'suppressions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'type',
        'reason',
        'source',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Suppression type constants.
     */
    public const TYPE_UNSUB = 'unsub';
    public const TYPE_BOUNCE = 'bounce';
    public const TYPE_COMPLAINT = 'complaint';
    public const TYPE_MANUAL = 'manual';

    /**
     * Source constants.
     */
    public const SOURCE_POSTMARK = 'postmark';
    public const SOURCE_USER = 'user';
    public const SOURCE_ADMIN = 'admin';

    /**
     * Check if an email is suppressed.
     *
     * @param string $email
     * @return bool
     */
    public static function isSuppressed(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))->exists();
    }

    /**
     * Add an email to the suppression list.
     *
     * @param string $email
     * @param string $type
     * @param string|null $reason
     * @param string $source
     * @param array|null $meta
     * @return static
     */
    public static function suppress(
        string $email,
        string $type,
        ?string $reason = null,
        string $source = self::SOURCE_ADMIN,
        ?array $meta = null
    ): static {
        return static::firstOrCreate(
            ['email' => strtolower(trim($email))],
            [
                'type' => $type,
                'reason' => $reason,
                'source' => $source,
                'meta' => $meta,
            ]
        );
    }

    /**
     * Remove an email from the suppression list.
     *
     * @param string $email
     * @return bool
     */
    public static function unsuppress(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))->delete() > 0;
    }

    /**
     * Get or create a suppression for a bounced email.
     *
     * @param string $email
     * @param string|null $reason
     * @param array|null $meta
     * @return static
     */
    public static function fromBounce(string $email, ?string $reason = null, ?array $meta = null): static
    {
        return static::suppress($email, self::TYPE_BOUNCE, $reason, self::SOURCE_POSTMARK, $meta);
    }

    /**
     * Get or create a suppression for a spam complaint.
     *
     * @param string $email
     * @param string|null $reason
     * @param array|null $meta
     * @return static
     */
    public static function fromComplaint(string $email, ?string $reason = null, ?array $meta = null): static
    {
        return static::suppress($email, self::TYPE_COMPLAINT, $reason, self::SOURCE_POSTMARK, $meta);
    }

    /**
     * Get or create a suppression for an unsubscribe.
     *
     * @param string $email
     * @param string|null $reason
     * @param array|null $meta
     * @return static
     */
    public static function fromUnsubscribe(string $email, ?string $reason = null, ?array $meta = null): static
    {
        return static::suppress($email, self::TYPE_UNSUB, $reason, self::SOURCE_USER, $meta);
    }

    /**
     * Scope a query to only include suppressions of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include suppressions from a specific source.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $source
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope a query to only include unsubscribes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnsubscribes($query)
    {
        return $query->where('type', self::TYPE_UNSUB);
    }

    /**
     * Scope a query to only include bounces.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBounces($query)
    {
        return $query->where('type', self::TYPE_BOUNCE);
    }

    /**
     * Scope a query to only include complaints.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeComplaints($query)
    {
        return $query->where('type', self::TYPE_COMPLAINT);
    }

    /**
     * Scope a query to only include manual suppressions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManual($query)
    {
        return $query->where('type', self::TYPE_MANUAL);
    }
}

// CLAUDE-CHECKPOINT
