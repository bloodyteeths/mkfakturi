<?php

namespace App\Helpers;

use Laravel\Pennant\Feature;

/**
 * Feature Flag Helper
 *
 * Provides convenient methods for checking feature flags throughout the application.
 * All features default to OFF except partner_mocked_data which defaults to ON for safety.
 */
class FeatureHelper
{
    /**
     * Check if accounting backbone is enabled.
     *
     * When enabled, Invoice/Payment events post to eloquent-ifrs ledger.
     */
    public static function accountingEnabled(): bool
    {
        return Feature::active('accounting-backbone');
    }

    /**
     * Check if migration wizard is enabled.
     *
     * When enabled, CSV/XLSX import wizard is available with Onivo/Megasoft presets.
     */
    public static function migrationWizardEnabled(): bool
    {
        return Feature::active('migration-wizard');
    }

    /**
     * Check if PSD2 banking is enabled.
     *
     * When enabled, OAuth bank connections and transaction sync are available.
     */
    public static function psd2BankingEnabled(): bool
    {
        return Feature::active('psd2-banking');
    }

    /**
     * Check if partner portal is enabled.
     *
     * When enabled, partner APIs are available (respecting mocked data flag).
     */
    public static function partnerPortalEnabled(): bool
    {
        return Feature::active('partner-portal');
    }

    /**
     * Check if partner mocked data flag is ON.
     *
     * SAFETY: This defaults to TRUE. Only flip to false after staging validation.
     * When ON, partner APIs return mocked data instead of real database queries.
     */
    public static function partnerMockedData(): bool
    {
        return Feature::active('partner-mocked-data');
    }

    /**
     * Check if advanced payments are enabled.
     *
     * When enabled, Paddle and CPAY payment gateways are active.
     */
    public static function advancedPaymentsEnabled(): bool
    {
        return Feature::active('advanced-payments');
    }

    /**
     * Check if MCP AI tools are enabled.
     *
     * When enabled, MCP server tools (UBL validation, tax explanations) are available.
     */
    public static function mcpAiToolsEnabled(): bool
    {
        return Feature::active('mcp-ai-tools');
    }

    /**
     * Check if monitoring is enabled.
     *
     * When enabled, Prometheus metrics and Telescope debugging are available.
     */
    public static function monitoringEnabled(): bool
    {
        return Feature::active('monitoring');
    }

    /**
     * Get all feature flags with their current states.
     *
     * @return array<string, bool>
     */
    public static function getAllFeatures(): array
    {
        return [
            'accounting-backbone' => self::accountingEnabled(),
            'migration-wizard' => self::migrationWizardEnabled(),
            'psd2-banking' => self::psd2BankingEnabled(),
            'partner-portal' => self::partnerPortalEnabled(),
            'partner-mocked-data' => self::partnerMockedData(),
            'advanced-payments' => self::advancedPaymentsEnabled(),
            'mcp-ai-tools' => self::mcpAiToolsEnabled(),
            'monitoring' => self::monitoringEnabled(),
        ];
    }
}

// CLAUDE-CHECKPOINT
