<?php

namespace Modules\Mk\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Mk\Services\BarcodeService;
use Modules\Mk\Services\QrCodeService;

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
            return new BarcodeService();
        });

        // Register QrCodeService as singleton
        $this->app->singleton(QrCodeService::class, function ($app) {
            return new QrCodeService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

// CLAUDE-CHECKPOINT
