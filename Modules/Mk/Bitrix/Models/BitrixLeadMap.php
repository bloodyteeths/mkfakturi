<?php

namespace Modules\Mk\Bitrix\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BitrixLeadMap Model
 *
 * Maps local OutreachLead records to Bitrix24 lead IDs.
 * Enables bidirectional sync between Facturino and Bitrix24.
 *
 * @property int $id
 * @property int $outreach_lead_id
 * @property string $bitrix_lead_id
 * @property string|null $bitrix_status_id
 * @property \Carbon\Carbon|null $last_synced_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class BitrixLeadMap extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bitrix_lead_maps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'outreach_lead_id',
        'bitrix_lead_id',
        'bitrix_status_id',
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
     * Find a mapping by Bitrix24 lead ID.
     *
     * @param string $bitrixLeadId
     * @return static|null
     */
    public static function findByBitrixId(string $bitrixLeadId): ?static
    {
        return static::where('bitrix_lead_id', $bitrixLeadId)->first();
    }

    /**
     * Create or update a mapping for an outreach lead.
     *
     * @param int $outreachLeadId
     * @param string $bitrixLeadId
     * @param string|null $bitrixStatusId
     * @return static
     */
    public static function syncMapping(
        int $outreachLeadId,
        string $bitrixLeadId,
        ?string $bitrixStatusId = null
    ): static {
        return static::updateOrCreate(
            ['outreach_lead_id' => $outreachLeadId],
            [
                'bitrix_lead_id' => $bitrixLeadId,
                'bitrix_status_id' => $bitrixStatusId,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * Mark this mapping as synced.
     *
     * @param string|null $statusId Optional new status ID
     * @return bool
     */
    public function markSynced(?string $statusId = null): bool
    {
        $data = ['last_synced_at' => now()];

        if ($statusId !== null) {
            $data['bitrix_status_id'] = $statusId;
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
}

// CLAUDE-CHECKPOINT
