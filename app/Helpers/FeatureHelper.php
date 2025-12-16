<?php

namespace App\Helpers;

use Laravel\Pennant\Feature;

/**
 * Feature Flag Helper
 *
 * Provides convenient methods for checking feature flags throughout the application.
 * All features default to OFF for safety until enabled in production.
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
     * When enabled, partner APIs are available with real data.
     */
    public static function partnerPortalEnabled(): bool
    {
        return Feature::active('partner-portal');
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
     * Check if Redis-backed queues are enabled.
     *
     * When enabled, queue workers can safely use Redis connections.
     */
    public static function redisQueuesEnabled(): bool
    {
        return Feature::active('redis-queues');
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
            'advanced-payments' => self::advancedPaymentsEnabled(),
            'mcp-ai-tools' => self::mcpAiToolsEnabled(),
            'monitoring' => self::monitoringEnabled(),
            'redis-queues' => self::redisQueuesEnabled(),
        ];
    }
}

// CLAUDE-CHECKPOINT
