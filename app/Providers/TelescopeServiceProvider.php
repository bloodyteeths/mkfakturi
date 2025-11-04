<?php

namespace App\Providers;

use App\Space\InstallUtils;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Don't enable Telescope during installation or if monitoring feature is disabled
        if (!InstallUtils::isDbCreated()) {
            return;
        }

        // Check if monitoring feature is enabled
        $monitoringEnabled = config('features.monitoring.enabled', false);
        if (!$monitoringEnabled) {
            return;
        }

        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     * CLAUDE-CHECKPOINT
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            // Check if monitoring feature is enabled
            $monitoringEnabled = config('app.features.monitoring', false)
                || env('FEATURE_MONITORING', false);

            if (!$monitoringEnabled) {
                return false;
            }

            // Check if user is an admin (using role check)
            // Assuming the user has a role relationship or method
            return $user->role === 'super admin' || $user->isOwner();
        });
    }
}
