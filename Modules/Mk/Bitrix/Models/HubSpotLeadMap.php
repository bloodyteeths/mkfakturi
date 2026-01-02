<?php

namespace Modules\Mk\Bitrix\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HubSpotLeadMap Model
 *
 * Maps local OutreachLead records to HubSpot CRM entity IDs.
 * Enables bidirectional sync between Facturino and HubSpot.
 *
 * @property int $id
 * @property string $email
 * @property int|null $outreach_lead_id
 * @property string|null $hubspot_contact_id
 * @property string|null $hubspot_company_id
 * @property string|null $hubspot_deal_id
 * @property string|null $deal_stage
 * @property \Carbon\Carbon|null $last_synced_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class HubSpotLeadMap extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hubspot_mappings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'outreach_lead_id',
        'hubspot_contact_id',
        'hubspot_company_id',
        'hubspot_deal_id',
        'deal_stage',
        'last_synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the outreach lead this mapping belongs to.
     *
     * @return BelongsTo
     */
    public function outreachLead(): BelongsTo
    {
        return $this->belongsTo(OutreachLead::class, 'outreach_lead_id');
    }

    /**
     * Find a mapping by email.
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail(string $email): ?static
    {
        return static::where('email', strtolower($email))->first();
    }

    /**
     * Find a mapping by HubSpot contact ID.
     *
     * @param string $contactId
     * @return static|null
     */
    public static function findByContactId(string $contactId): ?static
    {
        return static::where('hubspot_contact_id', $contactId)->first();
    }

    /**
     * Find a mapping by HubSpot deal ID.
     *
     * @param string $dealId
     * @return static|null
     */
    public static function findByDealId(string $dealId): ?static
    {
        return static::where('hubspot_deal_id', $dealId)->first();
    }

    /**
     * Create or update a mapping for an outreach lead.
     *
     * @param string $email Lead email
     * @param int|null $outreachLeadId Local outreach lead ID
     * @param string|null $contactId HubSpot contact ID
     * @param string|null $companyId HubSpot company ID
     * @param string|null $dealId HubSpot deal ID
     * @param string|null $dealStage Current deal stage
     * @return static
     */
    public static function syncMapping(
        string $email,
        ?int $outreachLeadId = null,
        ?string $contactId = null,
        ?string $companyId = null,
        ?string $dealId = null,
        ?string $dealStage = null
    ): static {
        $data = [
            'last_synced_at' => now(),
        ];

        if ($outreachLeadId !== null) {
            $data['outreach_lead_id'] = $outreachLeadId;
        }

        if ($contactId !== null) {
            $data['hubspot_contact_id'] = $contactId;
        }

        if ($companyId !== null) {
            $data['hubspot_company_id'] = $companyId;
        }

        if ($dealId !== null) {
            $data['hubspot_deal_id'] = $dealId;
        }

        if ($dealStage !== null) {
            $data['deal_stage'] = $dealStage;
        }

        return static::updateOrCreate(
            ['email' => strtolower($email)],
            $data
        );
    }

    /**
     * Update HubSpot IDs after sync.
     *
     * @param string|null $contactId HubSpot contact ID
     * @param string|null $companyId HubSpot company ID
     * @param string|null $dealId HubSpot deal ID
     * @return bool
     */
    public function updateHubSpotIds(
        ?string $contactId = null,
        ?string $companyId = null,
        ?string $dealId = null
    ): bool {
        $data = ['last_synced_at' => now()];

        if ($contactId !== null) {
            $data['hubspot_contact_id'] = $contactId;
        }

        if ($companyId !== null) {
            $data['hubspot_company_id'] = $companyId;
        }

        if ($dealId !== null) {
            $data['hubspot_deal_id'] = $dealId;
        }

        return $this->update($data);
    }

    /**
     * Update deal stage.
     *
     * @param string $stage Deal stage
     * @return bool
     */
    public function updateDealStage(string $stage): bool
    {
        return $this->update([
            'deal_stage' => $stage,
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Mark this mapping as synced.
     *
     * @param string|null $dealStage Optional new deal stage
     * @return bool
     */
    public function markSynced(?string $dealStage = null): bool
    {
        $data = ['last_synced_at' => now()];

        if ($dealStage !== null) {
            $data['deal_stage'] = $dealStage;
        }

        return $this->update($data);
    }

    /**
     * Scope a query to only include mappings that need syncing.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minutes Sync threshold in minutes
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsSync($query, int $minutes = 60)
    {
        return $query->where(function ($q) use ($minutes) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subMinutes($minutes));
        });
    }

    /**
     * Scope a query to only include mappings with a HubSpot contact.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasContact($query)
    {
        return $query->whereNotNull('hubspot_contact_id');
    }

    /**
     * Scope a query to only include mappings with a HubSpot deal.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasDeal($query)
    {
        return $query->whereNotNull('hubspot_deal_id');
    }

    /**
     * Check if this mapping has a HubSpot contact.
     *
     * @return bool
     */
    public function hasContact(): bool
    {
        return $this->hubspot_contact_id !== null;
    }

    /**
     * Check if this mapping has a HubSpot company.
     *
     * @return bool
     */
    public function hasCompany(): bool
    {
        return $this->hubspot_company_id !== null;
    }

    /**
     * Check if this mapping has a HubSpot deal.
     *
     * @return bool
     */
    public function hasDeal(): bool
    {
        return $this->hubspot_deal_id !== null;
    }
}

// CLAUDE-CHECKPOINT
