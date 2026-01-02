<?php

namespace Modules\Mk\Bitrix\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * OutreachEvent Model
 *
 * Represents a webhook event from Postmark email service.
 * Tracks delivery, opens, clicks, bounces, spam complaints, and other email events.
 * Used for idempotent processing of Postmark webhooks.
 *
 * @property int $id
 * @property string $provider Email provider (e.g., 'postmark')
 * @property string|null $event_id Unique event identifier for idempotency (MessageID-EventType-Timestamp)
 * @property string $event_type Type of event (Delivery, Open, Click, Bounce, SpamComplaint)
 * @property string|null $postmark_message_id Postmark MessageID
 * @property string|null $recipient_email Recipient email address
 * @property array|null $payload Full webhook payload from Postmark
 * @property \Carbon\Carbon|null $processed_at When event was processed
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class OutreachEvent extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'outreach_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'postmark_message_id',
        'recipient_email',
        'payload',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Event type constants.
     */
    public const TYPE_DELIVERED = 'delivered';
    public const TYPE_OPENED = 'opened';
    public const TYPE_CLICKED = 'clicked';
    public const TYPE_BOUNCED = 'bounced';
    public const TYPE_SPAM_COMPLAINT = 'spam_complaint';
    public const TYPE_UNSUBSCRIBED = 'unsubscribed';
    public const TYPE_SUBSCRIPTION_CHANGED = 'subscription_changed';
    public const TYPE_LINK_CLICKED = 'link_clicked';

    /**
     * Record a Postmark webhook event.
     *
     * @param string $eventType Postmark event type (Delivery, Open, Click, Bounce, SpamComplaint)
     * @param string $messageId Postmark MessageID
     * @param string $email Recipient email
     * @param array $payload Full webhook payload
     * @return static
     */
    public static function recordPostmarkEvent(
        string $eventType,
        string $messageId,
        string $email,
        array $payload
    ): static {
        $timestamp = $payload['Timestamp'] ?? time();
        $eventId = $messageId . '-' . $eventType . '-' . $timestamp;

        return static::create([
            'provider' => 'postmark',
            'event_id' => $eventId,
            'event_type' => $eventType,
            'postmark_message_id' => $messageId,
            'recipient_email' => $email,
            'payload' => $payload,
        ]);
    }

    /**
     * Scope a query to only include events of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $eventType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope a query to only include events by Postmark message ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $messageId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMessage($query, string $messageId)
    {
        return $query->where('postmark_message_id', $messageId);
    }

    /**
     * Scope a query to only include events for a specific email.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $email
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('recipient_email', $email);
    }

    /**
     * Scope a query to only include click events.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClicks($query)
    {
        return $query->where('event_type', 'Click');
    }

    /**
     * Scope a query to only include open events.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpens($query)
    {
        return $query->where('event_type', 'Open');
    }

    /**
     * Scope a query to only include negative events (bounces, spam complaints).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNegative($query)
    {
        return $query->whereIn('event_type', ['Bounce', 'SpamComplaint']);
    }

    /**
     * Scope a query to only include events from a specific provider.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope a query to only include unprocessed events.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Find an event by provider and event_id.
     *
     * @param string $provider
     * @param string $eventId
     * @return static|null
     */
    public static function findByProviderEventId(string $provider, string $eventId): ?static
    {
        return static::where('provider', $provider)
            ->where('event_id', $eventId)
            ->first();
    }

    /**
     * Find events by Postmark message ID.
     *
     * @param string $messageId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findByPostmarkMessageId(string $messageId)
    {
        return static::where('postmark_message_id', $messageId)->get();
    }
}

// CLAUDE-CHECKPOINT
