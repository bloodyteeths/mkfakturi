<?php

namespace App\Providers;

use App\Space\InstallUtils;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
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
        // Skip view sharing for health checks and console commands
        // This prevents database queries during health checks and startup
        if ($this->isHealthCheckRequest() || $this->app->runningInConsole()) {
            return;
        }

        // Skip view sharing if database not created
        if (! InstallUtils::isDbCreated()) {
            return;
        }

        View::share('admin_logo', logo_asset_url(get_app_setting('admin_portal_logo')));
        View::share('login_page_logo', logo_asset_url(get_app_setting('login_page_logo')));
        View::share('login_page_heading', get_app_setting('login_page_heading'));
        View::share('login_page_description', get_app_setting('login_page_description'));
        View::share('admin_page_title', get_app_setting('admin_page_title'));
        View::share('copyright_text', get_app_setting('copyright_text'));
    }

    /**
     * Check if current request is a health check endpoint
     */
    protected function isHealthCheckRequest(): bool
    {
        if (! $this->app->bound('request')) {
            return false;
        }

        $request = $this->app->make('request');
        $healthCheckPaths = ['/health', '/up', '/ping', '/ready'];

        return in_array($request->path(), $healthCheckPaths);
    }
}

// CLAUDE-CHECKPOINT
