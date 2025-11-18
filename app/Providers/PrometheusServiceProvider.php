<?php

namespace App\Providers;

use Arquivei\LaravelPrometheusExporter\PrometheusExporter;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PrometheusExporter::class, function ($app) {
            $namespace = config('prometheus-exporter.namespace', 'fakturino');
            $adapter = new InMemory;
            $registry = new CollectorRegistry($adapter);

            return new PrometheusExporter($namespace, $registry);
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
