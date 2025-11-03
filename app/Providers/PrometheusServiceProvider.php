<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Arquivei\LaravelPrometheusExporter\PrometheusExporter;

/**
 * Prometheus Service Provider
 *
 * Registers the Prometheus exporter (arquivei package handles the setup)
 * CLAUDE-CHECKPOINT
 */
class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The arquivei package handles registration automatically
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/prometheus.php' => config_path('prometheus.php'),
            ], 'prometheus-config');
        }
    }
}