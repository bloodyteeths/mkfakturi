<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\CacheServiceProvider::class,
    App\Providers\DropboxServiceProvider::class,
    App\Providers\PDFServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    // Temporarily disabled during installation - causes database errors
    // App\Providers\TelescopeServiceProvider::class,
    App\Providers\ViewServiceProvider::class,
];
