<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Superbalist\LaravelPrometheusExporter\PrometheusExporter;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Prometheus\Storage\APC;

/**
 * Prometheus Service Provider
 * 
 * Registers the Prometheus exporter and configures storage adapters
 */
class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PrometheusExporter::class, function ($app) {
            $config = config('prometheus');
            $adapter = $this->createStorageAdapter($config);
            $registry = new CollectorRegistry($adapter);
            
            return new PrometheusExporter($config['namespace'], $registry);
        });
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

    /**
     * Create storage adapter based on configuration
     */
    protected function createStorageAdapter(array $config)
    {
        $adapterName = $config['storage_adapter'];
        $adapterConfig = $config['storage_adapters'][$adapterName];

        switch ($adapterName) {
            case 'redis':
                return new Redis([
                    'host' => $adapterConfig['host'],
                    'port' => $adapterConfig['port'],
                    'password' => $adapterConfig['password'],
                    'database' => $adapterConfig['database'],
                    'timeout' => $adapterConfig['timeout'],
                    'read_timeout' => $adapterConfig['read_timeout'],
                    'persistent' => $adapterConfig['persistent_connections'],
                    'prefix' => $adapterConfig['prefix'],
                ]);

            case 'apc':
                return new APC();

            case 'memory':
            default:
                return new InMemory();
        }
    }
}