<?php

namespace Modules\Mk\Bitrix\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * OutreachLead Model
 *
 * Represents a lead in the outreach system.
 * Stores local lead data that syncs with HubSpot CRM.
 *
 * @property int $id
 * @property string $email
 * @property string|null $company_name
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $city
 * @property string|null $website
 * @property string $source
 * @property string|null $source_url
 * @property string $status
 * @property int|null $partner_id
 * @property \Carbon\Carbon|null $next_followup_at
 * @property \Carbon\Carbon|null $last_contacted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class OutreachLead extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'outreach_leads';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'company_name',
        'contact_name',
        'phone',
        'city',
        'website',
        'source',
        'source_url',
        'status',
        'partner_id',
        'next_followup_at',
        'last_contacted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'next_followup_at' => 'datetime',
        'last_contacted_at' => 'datetime',
    ];

    /**
     * Lead source constants.
     */
    public const SOURCE_ISOS = 'isos';
    public const SOURCE_SMETKOVODITELI = 'smetkovoditeli';
    public const SOURCE_MANUAL = 'manual';

    /**
     * Lead status constants.
     */
    public const STATUS_NEW = 'new';
    public const STATUS_EMAILED = 'emailed';
    public const STATUS_FOLLOWUP = 'followup';
    public const STATUS_INTERESTED = 'interested';
    public const STATUS_INVITE_SENT = 'invite_sent';
    public const STATUS_PARTNER_ACTIVE = 'partner_active';
    public const STATUS_LOST = 'lost';

    /**
     * Get the HubSpot mapping for this lead.
     *
     * @return HasOne
     */
    public function hubspotMapping(): HasOne
    {
        return $this->hasOne(HubSpotMapping::class, 'outreach_lead_id');
    }

    /**
     * Get all sends for this lead.
     *
     * @return HasMany
     */
    public function sends(): HasMany
    {
        return $this->hasMany(OutreachSend::class, 'outreach_lead_id');
    }

    /**
     * Get suppressions for this lead's email.
     *
     * @return HasMany
     */
    public function suppressions(): HasMany
    {
        return $this->hasMany(Suppression::class, 'email', 'email');
    }

    /**
     * Get all events for this lead.
     *
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(OutreachEvent::class, 'recipient_email', 'email');
    }

    /**
     * Get the HubSpot contact ID if mapped.
     *
     * @return string|null
     */
    public function getHubspotContactIdAttribute(): ?string
    {
        return $this->hubspotMapping?->hubspot_contact_id;
    }

    /**
     * Get the HubSpot deal ID if mapped.
     *
     * @return string|null
     */
    public function getHubspotDealIdAttribute(): ?string
    {
        return $this->hubspotMapping?->hubspot_deal_id;
    }

    /**
     * Get the HubSpot company ID if mapped.
     *
     * @return string|null
     */
    public function getHubspotCompanyIdAttribute(): ?string
    {
        return $this->hubspotMapping?->hubspot_company_id;
    }

    /**
     * Scope a query to only include leads that are not suppressed.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotSuppressed($query)
    {
        return $query->whereNotExists(function ($subquery) {
            $subquery->select(\DB::raw(1))
                ->from('suppressions')
                ->whereColumn('suppressions.email', 'outreach_leads.email');
        });
    }

    /**
     * Scope a query to only include leads ready for outreach.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReadyForOutreach($query)
    {
        return $query->notSuppressed()
            ->whereIn('status', [self::STATUS_NEW, self::STATUS_FOLLOWUP])
            ->where(function ($q) {
                $q->whereNull('next_followup_at')
                    ->orWhere('next_followup_at', '<=', now());
            });
    }

    /**
     * Scope a query to only include leads in a specific stage/status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $stage
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInStage($query, string $stage)
    {
        return $query->where('status', $stage);
    }

    /**
     * Scope a query to only include leads with a specific status.
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
     * Scope a query to only include leads that haven't been contacted recently.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotContactedWithinDays($query, int $days)
    {
        return $query->where(function ($q) use ($days) {
            $q->whereNull('last_contacted_at')
              ->orWhere('last_contacted_at', '<', now()->subDays($days));
        });
    }

    /**
     * Scope a query to only include leads eligible for outreach.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEligibleForOutreach($query)
    {
        return $query->whereIn('status', [
            self::STATUS_NEW,
            self::STATUS_FOLLOWUP,
        ]);
    }

    /**
     * Check if this lead is suppressed.
     *
     * @return bool
     */
    public function isSuppressed(): bool
    {
        return Suppression::isSuppressed($this->email);
    }

    /**
     * Mark this lead as contacted.
     *
     * @return bool
     */
    public function markContacted(): bool
    {
        return $this->update([
            'last_contacted_at' => now(),
            'status' => self::STATUS_EMAILED,
        ]);
    }

    /**
     * Schedule a follow-up for this lead.
     *
     * @param \Carbon\Carbon|null $date
     * @return bool
     */
    public function scheduleFollowup(?\Carbon\Carbon $date = null): bool
    {
        return $this->update([
            'next_followup_at' => $date ?? now()->addDays(3),
            'status' => self::STATUS_FOLLOWUP,
        ]);
    }
}

// CLAUDE-CHECKPOINT
