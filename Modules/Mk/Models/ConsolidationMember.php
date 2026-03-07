<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsolidationMember extends Model
{
    protected $table = 'consolidation_members';

    protected $fillable = [
        'group_id',
        'company_id',
        'ownership_pct',
        'is_parent',
    ];

    protected function casts(): array
    {
        return [
            'group_id' => 'integer',
            'company_id' => 'integer',
            'ownership_pct' => 'decimal:2',
            'is_parent' => 'boolean',
        ];
    }

    // ---- Relationships ----

    public function group(): BelongsTo
    {
        return $this->belongsTo(ConsolidationGroup::class, 'group_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

// CLAUDE-CHECKPOINT
