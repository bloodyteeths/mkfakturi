<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\CacheServiceProvider::class,
    App\Providers\DropboxServiceProvider::class,
    App\Providers\FeatureFlagServiceProvider::class,
    App\Providers\PDFServiceProvider::class,
    App\Providers\PrometheusServiceProvider::class,
    // RouteServiceProvider removed - routing handled in bootstrap/app.php (Laravel 12)
    // Enabled only when FEATURE_MONITORING is true (via service provider check)
    App\Providers\TelescopeServiceProvider::class,
    App\Providers\ViewServiceProvider::class,
];
// CLAUDE-CHECKPOINT
