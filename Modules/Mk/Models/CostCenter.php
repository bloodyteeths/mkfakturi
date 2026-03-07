<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class CostCenter extends Model
{
    use SoftDeletes;

    protected $table = 'cost_centers';

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'code',
        'color',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(CostCenterRule::class);
    }

    // ---- Scopes ----

    /**
     * Scope to a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('cost_centers.company_id', $companyId);
    }

    /**
     * Scope to active cost centers only.
     */
    public function scopeActive($query)
    {
        return $query->where('cost_centers.is_active', true);
    }

    /**
     * Scope to top-level cost centers (no parent).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('cost_centers.parent_id');
    }

    // ---- Tree Helpers ----

    /**
     * Get all descendants (recursive children) as a flat collection.
     */
    public function descendants(): Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Get all ancestors from this node up to the root.
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors->reverse()->values();
    }

    /**
     * Get the full path as a breadcrumb string (e.g., "Parent > Child > Grandchild").
     */
    public function fullPath(): string
    {
        $path = $this->ancestors()->pluck('name')->toArray();
        $path[] = $this->name;

        return implode(' > ', $path);
    }

    /**
     * Check if this cost center has any active children.
     */
    public function hasActiveChildren(): bool
    {
        return $this->children()->where('is_active', true)->exists();
    }

    /**
     * Get depth level in the tree (0 = top level).
     */
    public function getDepthAttribute(): int
    {
        return $this->ancestors()->count();
    }
}

// CLAUDE-CHECKPOINT
