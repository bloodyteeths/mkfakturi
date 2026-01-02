<?php

namespace Modules\Mk\Bitrix\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * OutreachSend Model
 *
 * Represents an email send to an outreach lead.
 * Tracks email sending status, Postmark message IDs, and delivery info.
 *
 * @property int $id
 * @property string $email
 * @property int|null $outreach_lead_id
 * @property string $template_key
 * @property string|null $postmark_message_id
 * @property string $status
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $opened_at
 * @property \Carbon\Carbon|null $clicked_at
 * @property int $open_count
 * @property int $click_count
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class OutreachSend extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'outreach_sends';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'outreach_lead_id',
        'template_key',
        'postmark_message_id',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'open_count',
        'click_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
    ];

    /**
     * Template key constants.
     */
    public const TEMPLATE_FIRST_TOUCH = 'first_touch';
    public const TEMPLATE_FOLLOWUP_1 = 'followup_1';
    public const TEMPLATE_FOLLOWUP_2 = 'followup_2';

    /**
     * Send status constants.
     */
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_OPENED = 'opened';
    public const STATUS_CLICKED = 'clicked';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_COMPLAINED = 'complained';

    /**
     * Get the outreach lead this send belongs to.
     *
     * @return BelongsTo
     */
    public function outreachLead(): BelongsTo
    {
        return $this->belongsTo(OutreachLead::class, 'outreach_lead_id');
    }

    /**
     * Get all events for this send (via postmark_message_id).
     *
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(OutreachEvent::class, 'postmark_message_id', 'postmark_message_id');
    }

    /**
     * Mark this send as sent with Postmark message ID.
     *
     * @param string $messageId Postmark message ID
     * @return bool
     */
    public function markSent(string $messageId): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'postmark_message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark this send as delivered.
     *
     * @return bool
     */
    public function markDelivered(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark this send as opened.
     *
     * @return bool
     */
    public function markOpened(): bool
    {
        $data = [
            'open_count' => $this->open_count + 1,
        ];

        // Only update status and opened_at on first open
        if (!$this->opened_at) {
            $data['status'] = self::STATUS_OPENED;
            $data['opened_at'] = now();
        }

        return $this->update($data);
    }

    /**
     * Mark this send as clicked.
     *
     * @return bool
     */
    public function markClicked(): bool
    {
        $data = [
            'click_count' => $this->click_count + 1,
        ];

        // Only update status and clicked_at on first click
        if (!$this->clicked_at) {
            $data['status'] = self::STATUS_CLICKED;
            $data['clicked_at'] = now();
        }

        return $this->update($data);
    }

    /**
     * Mark this send as bounced.
     *
     * @return bool
     */
    public function markBounced(): bool
    {
        return $this->update([
            'status' => self::STATUS_BOUNCED,
        ]);
    }

    /**
     * Mark this send as complained (spam complaint).
     *
     * @return bool
     */
    public function markComplained(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLAINED,
        ]);
    }

    /**
     * Find a send by Postmark message ID.
     *
     * @param string $messageId
     * @return static|null
     */
    public static function findByPostmarkId(string $messageId): ?static
    {
        return static::where('postmark_message_id', $messageId)->first();
    }

    /**
     * Scope a query to only include sends with a specific status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include sends from today.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to only include sends from the last hour.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLastHour($query)
    {
        return $query->where('created_at', '>=', now()->subHour());
    }

    /**
     * Scope a query to only include queued sends.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeQueued($query)
    {
        return $query->where('status', self::STATUS_QUEUED);
    }

    /**
     * Scope a query to only include sent (but not delivered yet) sends.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope a query to only include bounced sends.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBounced($query)
    {
        return $query->where('status', self::STATUS_BOUNCED);
    }
}

// CLAUDE-CHECKPOINT
