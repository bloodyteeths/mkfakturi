<?php

namespace App\Providers;

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
     */
    protected function defineFeatures(): void
    {
        Feature::define('accounting-backbone', function () {
            return config('features.accounting_backbone.enabled', false);
        });

        Feature::define('migration-wizard', function () {
            return config('features.migration_wizard.enabled', false);
        });

        Feature::define('psd2-banking', function () {
            return config('features.psd2_banking.enabled', false);
        });

        Feature::define('partner-portal', function () {
            return config('features.partner_portal.enabled', false);
        });

        Feature::define('partner-mocked-data', function () {
            return config('features.partner_mocked_data.enabled', true);  // SAFETY
        });

        Feature::define('advanced-payments', function () {
            return config('features.advanced_payments.enabled', false);
        });

        Feature::define('mcp-ai-tools', function () {
            return config('features.mcp_ai_tools.enabled', false);
        });

        Feature::define('monitoring', function () {
            return config('features.monitoring.enabled', false);
        });
    }
}

// CLAUDE-CHECKPOINT
