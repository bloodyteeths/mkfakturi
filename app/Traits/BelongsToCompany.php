<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * BelongsToCompany Trait
 *
 * Provides explicit company-scoped query methods for multi-tenant models.
 * Unlike TenantScope, this trait does NOT add global scopes (which are
 * dangerous in queue workers and cause hard-to-debug data leaks).
 *
 * Usage:
 *   BankTransaction::forCompany($companyId)->where('id', $id)->first();
 *
 * @see P0-13 Tenant Scoping Audit
 */
trait BelongsToCompany
{
    /**
     * Boot the BelongsToCompany trait.
     *
     * Auto-sets company_id on model creation if not already set
     * and an authenticated user with a company is available.
     * Does NOT add global scopes (safe for queues).
     */
    protected static function bootBelongsToCompany(): void
    {
        static::creating(function ($model) {
            if (! $model->company_id && auth()->check()) {
                $user = auth()->user();
                // Try request header first (InvoiceShelf convention)
                $companyId = request()->header('company');
                if ($companyId) {
                    $model->company_id = (int) $companyId;
                } elseif (method_exists($user, 'companies')) {
                    $firstCompany = $user->companies()->first();
                    if ($firstCompany) {
                        $model->company_id = $firstCompany->id;
                    }
                }
            }
        });
    }

    /**
     * Scope: Filter query to a specific company.
     *
     * This is the primary method for tenant isolation. All banking/reconciliation
     * queries MUST use this scope (or an equivalent explicit WHERE clause).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $companyId  The company to scope to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where($this->getTable() . '.company_id', $companyId);
    }
}

// CLAUDE-CHECKPOINT
