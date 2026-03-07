<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsolidationGroup extends Model
{
    use SoftDeletes;

    protected $table = 'consolidation_groups';

    protected $fillable = [
        'partner_id',
        'name',
        'parent_company_id',
        'currency_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'partner_id' => 'integer',
            'parent_company_id' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function members(): HasMany
    {
        return $this->hasMany(ConsolidationMember::class, 'group_id');
    }

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    // ---- Scopes ----

    /**
     * Scope to a specific partner.
     */
    public function scopeForPartner($query, int $partnerId)
    {
        return $query->where('consolidation_groups.partner_id', $partnerId);
    }
}

// CLAUDE-CHECKPOINT
