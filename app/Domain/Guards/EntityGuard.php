<?php

namespace App\Domain\Guards;

use App\Models\Company;
use DomainException;

/**
 * EntityGuard
 *
 * Domain service to enforce that a company has an IFRS entity
 * before performing accounting operations.
 *
 * Usage:
 *   EntityGuard::ensureEntityExists($company);
 */
class EntityGuard
{
    /**
     * Ensure that a company has an IFRS entity.
     * Throws DomainException if entity is missing.
     *
     * @throws DomainException
     */
    public static function ensureEntityExists(Company $company): void
    {
        if (! $company->ifrs_entity_id) {
            throw new DomainException(
                "Company '{$company->name}' (ID: {$company->id}) has no IFRS entity. ".
                'Run MkIfrsSeeder or enable accounting features to create the entity.'
            );
        }

        // Verify the entity actually exists in the database
        if (! $company->ifrsEntity()->exists()) {
            throw new DomainException(
                "Company '{$company->name}' (ID: {$company->id}) references a non-existent IFRS entity ".
                "(Entity ID: {$company->ifrs_entity_id}). Run MkIfrsSeeder to fix."
            );
        }
    }

    /**
     * Check if a company has an IFRS entity without throwing.
     */
    public static function hasEntity(Company $company): bool
    {
        return $company->ifrs_entity_id && $company->ifrsEntity()->exists();
    }

    /**
     * Ensure entity exists or return a friendly error message.
     * Use this for API responses where throwing exceptions is not desired.
     *
     * @return array|null Returns error array if entity missing, null if valid
     */
    public static function validateEntity(Company $company): ?array
    {
        if (! $company->ifrs_entity_id) {
            return [
                'error' => 'IFRS entity not configured',
                'message' => "Company '{$company->name}' does not have an IFRS entity configured. ".
                            'Please run the accounting setup to enable financial reporting.',
                'company_id' => $company->id,
                'company_name' => $company->name,
            ];
        }

        if (! $company->ifrsEntity()->exists()) {
            return [
                'error' => 'IFRS entity reference invalid',
                'message' => "Company '{$company->name}' has an invalid IFRS entity reference. ".
                            'Please contact support or run the accounting setup.',
                'company_id' => $company->id,
                'company_name' => $company->name,
                'entity_id' => $company->ifrs_entity_id,
            ];
        }

        return null;
    }
}

// CLAUDE-CHECKPOINT
