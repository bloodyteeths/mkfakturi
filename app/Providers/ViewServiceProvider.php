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
        // Skip view sharing if database not created or during console commands
        // This prevents database queries during health checks and startup
        if (!InstallUtils::isDbCreated() || $this->app->runningInConsole()) {
            return;
        }

        View::share('admin_logo', logo_asset_url(get_app_setting('admin_portal_logo')));
        View::share('login_page_logo', logo_asset_url(get_app_setting('login_page_logo')));
        View::share('login_page_heading', get_app_setting('login_page_heading'));
        View::share('login_page_description', get_app_setting('login_page_description'));
        View::share('admin_page_title', get_app_setting('admin_page_title'));
        View::share('copyright_text', get_app_setting('copyright_text'));
    }
}

// CLAUDE-CHECKPOINT
