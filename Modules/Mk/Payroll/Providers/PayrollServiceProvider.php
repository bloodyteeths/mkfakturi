<?php

namespace Modules\Mk\Payroll\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Mk\Payroll\Services\BankPaymentFileService;
use Modules\Mk\Payroll\Services\MacedonianPayrollTaxService;
use Modules\Mk\Payroll\Services\PayrollCalculationService;
use Modules\Mk\Payroll\Services\PayrollGLService;

/**
 * Payroll Service Provider
 *
 * Registers all payroll services as singletons for dependency injection.
 */
class PayrollServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register MacedonianPayrollTaxService as singleton
        $this->app->singleton(MacedonianPayrollTaxService::class, function ($app) {
            return new MacedonianPayrollTaxService();
        });

        // Register PayrollCalculationService as singleton
        $this->app->singleton(PayrollCalculationService::class, function ($app) {
            return new PayrollCalculationService(
                $app->make(MacedonianPayrollTaxService::class)
            );
        });

        // Register PayrollGLService as singleton
        $this->app->singleton(PayrollGLService::class, function ($app) {
            return new PayrollGLService();
        });

        // Register BankPaymentFileService as singleton
        $this->app->singleton(BankPaymentFileService::class, function ($app) {
            return new BankPaymentFileService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Load migrations if they exist
        $migrationsPath = __DIR__ . '/../../database/migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // Load routes if they exist
        $routesPath = __DIR__ . '/../../routes';
        if (is_dir($routesPath)) {
            if (file_exists($routesPath . '/api.php')) {
                $this->loadRoutesFrom($routesPath . '/api.php');
            }
            if (file_exists($routesPath . '/web.php')) {
                $this->loadRoutesFrom($routesPath . '/web.php');
            }
        }

        // Load views if they exist
        $viewsPath = __DIR__ . '/../../resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'payroll');
        }
    }
}

// LLM-CHECKPOINT
