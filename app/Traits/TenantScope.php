<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * TenantScope Trait
 *
 * Automatically scopes queries to the current company (tenant) based on the company header.
 * This ensures multi-tenant data isolation at the model level.
 *
 * @package App\Traits
 */
trait TenantScope
{
    /**
     * Boot the TenantScope trait.
     * Adds a global scope to automatically filter by company_id.
     */
    protected static function bootTenantScope(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (request()->header('company')) {
                $builder->where($builder->getModel()->getTable() . '.company_id', request()->header('company'));
            }
        });
    }

    /**
     * Scope: filter by company ID.
     * Allows explicit company filtering even when global scope is removed.
     *
     * @param Builder $query
     * @param int|null $companyId
     * @return Builder
     */
    public function scopeWhereCompany(Builder $query, ?int $companyId = null): Builder
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where($this->getTable() . '.company_id', $companyId);
    }

    /**
     * Scope: remove company restriction (use with caution).
     * Only use for admin/system-level queries.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}

// CLAUDE-CHECKPOINT
