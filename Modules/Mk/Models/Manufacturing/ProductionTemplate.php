<?php

namespace Modules\Mk\Models\Manufacturing;

use App\Models\Company;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionTemplate extends Model
{
    use BelongsToCompany;

    protected $table = 'production_templates';

    protected $fillable = [
        'company_id',
        'bom_id',
        'name',
        'default_quantity',
        'frequency',
        'is_active',
        'ai_suggested',
        'last_generated_at',
        'next_generation_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'default_quantity' => 'decimal:4',
            'is_active' => 'boolean',
            'ai_suggested' => 'boolean',
            'last_generated_at' => 'datetime',
            'next_generation_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    // ---- Scopes ----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('next_generation_at', '<=', now());
    }
}

// CLAUDE-CHECKPOINT
