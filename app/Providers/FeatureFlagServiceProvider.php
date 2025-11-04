<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class FeatureFlagServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->defineFeatures();
    }

    /**
     * Define all Fakturino feature flags.
     *
     * All features default to OFF except partner_mocked_data which
     * defaults to ON for safety until staging validation complete.
     *
     * Priority order: Database setting > Config file > Default value
     */
    protected function defineFeatures(): void
    {
        Feature::define('accounting-backbone', function () {
            $dbValue = Setting::getSetting('feature_flag.accounting_backbone');
            return $dbValue !== null
                ? filter_var($dbValue, FILTER_VALIDATE_BOOLEAN)
                : config('features.accounting_backbone.enabled', false);
        });

        Feature::define('migration-wizard', function () {
            $dbValue = Setting::getSetting('feature_flag.migration_wizard');
            return $dbValue !== null
                ? filter_var($dbValue, FILTER_VALIDATE_BOOLEAN)
                : config('features.migration_wizard.enabled', false);
        });

        Feature::define('psd2-banking', function () {
            $dbValue = Setting::getSetting('feature_flag.psd2_banking');
            return $dbValue !== null
                ? filter_var($dbValue, FILTER_VALIDATE_BOOLEAN)
                : config('features.psd2_banking.enabled', false);
        });

        Feature::define('partner-portal', function () {
            $dbValue = Setting::getSetting('feature_flag.partner_portal');
            return $dbValue !== null
                ? filter_var($dbValue, FILTER_VALIDATE_BOOLEAN)
                : config('features.partner_portal.enabled', false);
        });

        Feature::define('partner-mocked-data', function () {
            $dbValue = Setting::getSetting('feature_flag.partner_mocked_data');
            return $dbValue !== null
                ? filter_var($dbValue, FILTER_VALIDATE_BOOLEAN)
                : config('features.partner_mocked_data.enabled', true);  // SAFETY DEFAULT
        });

        Feature::define('advanced-payments', function () {
            $dbValue = Setting::getSetting('feature_flag.advanced_payments');
            return $dbValue !== null
                ? filter_var($dbValue, FILTER_VALIDATE_BOOLEAN)
                : config('features.advanced_payments.enabled', false);
        });

        Feature::define('mcp-ai-tools', function () {
            $dbValue = Setting::getSetting('feature_flag.mcp_ai_tools');
            return $dbValue !== null
                ? filter_var($dbValue, FILTER_VALIDATE_BOOLEAN)
                : config('features.mcp_ai_tools.enabled', false);
        });

        Feature::define('monitoring', function () {
            $dbValue = Setting::getSetting('feature_flag.monitoring');
            return $dbValue !== null
                ? filter_var($dbValue, FILTER_VALIDATE_BOOLEAN)
                : config('features.monitoring.enabled', false);
        });
    }
}

// CLAUDE-CHECKPOINT
