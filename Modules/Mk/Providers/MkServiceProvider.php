<?php

namespace Modules\Mk\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Mk\Services\BarcodeService;
use Modules\Mk\Services\QrCodeService;
use Modules\Mk\Partner\Commands\ExpirePartnerTrials;

/**
 * Macedonian Module Service Provider
 *
 * Registers Macedonian-specific services including:
 * - Barcode generation service
 * - QR code generation service
 */
class MkServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * Registers services as singletons for better performance
     * when services are used multiple times in a request.
     */
    public function register(): void
    {
        // Register BarcodeService as singleton
        $this->app->singleton(BarcodeService::class, function ($app) {
            return new BarcodeService;
        });

        // Register QrCodeService as singleton
        $this->app->singleton(QrCodeService::class, function ($app) {
            return new QrCodeService;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Mk sub-module console commands that lack their own provider.
        // Without this, `partner:expire-trials` is "not defined" and its daily
        // cron fails every run (module commands are not auto-discovered).
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpirePartnerTrials::class,
            ]);
        }
        // CLAUDE-CHECKPOINT
    }
}

