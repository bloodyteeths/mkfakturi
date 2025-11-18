<?php

namespace App\Traits;

use Illuminate\Support\Facades\Request;

trait CachesPermissions
{
    /**
     * Get permission cache key for request scope
     */
    protected function getPermissionCacheKey(int $companyId): string
    {
        return "permissions.partner_{$this->id}.company_{$companyId}";
    }

    /**
     * Get cached permissions from request attributes
     */
    protected function getCachedPermissions(int $companyId): ?array
    {
        $key = $this->getPermissionCacheKey($companyId);

        return request()->attributes->get($key);
    }

    /**
     * Store permissions in request cache
     */
    protected function setCachedPermissions(int $companyId, array $permissions): void
    {
        $key = $this->getPermissionCacheKey($companyId);
        request()->attributes->set($key, $permissions);
    }

    /**
     * Clear permission cache for a company (called on assignment changes)
     */
    public function clearPermissionCache(int $companyId): void
    {
        $key = $this->getPermissionCacheKey($companyId);
        request()->attributes->remove($key);
    }

    /**
     * Get permissions for a company with request-scoped caching
     */
    protected function getPermissionsForCompany(int $companyId): array
    {
        // Check request cache first
        $cached = $this->getCachedPermissions($companyId);
        if ($cached !== null) {
            return $cached;
        }

        // Load from database
        $link = $this->companies()
            ->where('companies.id', $companyId)
            ->first();

        if (! $link) {
            $this->setCachedPermissions($companyId, []);

            return [];
        }

        $permissions = $link->pivot->permissions ?? [];

        // Decode JSON if it's a string
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        // Cache for this request
        $this->setCachedPermissions($companyId, $permissions);

        return $permissions;
    }
}

// CLAUDE-CHECKPOINT
