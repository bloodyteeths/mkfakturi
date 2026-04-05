<?php
/**
 * OPcache preload script - loads frequently used classes into shared memory.
 * This eliminates the ~1-2s framework boot overhead on every request.
 */

// Composer class map gives us all autoloadable classes
require __DIR__ . "/vendor/autoload.php";

// Preload Laravel framework core
$directories = [
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Container",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Support",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Pipeline",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Http",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Routing",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Database/Eloquent",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Database/Query",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Auth",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Session",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Cache",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Redis",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Events",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Foundation",
    __DIR__ . "/vendor/laravel/framework/src/Illuminate/Validation",
    // Sanctum auth
    __DIR__ . "/vendor/laravel/sanctum/src",
    // Bouncer permissions
    __DIR__ . "/vendor/silber/bouncer/src",
    // App models and middleware
    __DIR__ . "/app/Models",
    __DIR__ . "/app/Http/Middleware",
    __DIR__ . "/app/Http/Resources",
];

$count = 0;
foreach ($directories as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->getExtension() !== "php") continue;
        try {
            opcache_compile_file($file->getRealPath());
            $count++;
        } catch (\Throwable $e) {
            // Skip files that can not be preloaded (interfaces extending non-loaded interfaces, etc.)
        }
    }
}

// Also preload the cached config/routes if available
foreach ([
    __DIR__ . "/bootstrap/cache/config.php",
    __DIR__ . "/bootstrap/cache/routes-v7.php",
    __DIR__ . "/bootstrap/cache/services.php",
    __DIR__ . "/bootstrap/cache/events.php",
] as $cacheFile) {
    if (file_exists($cacheFile)) {
        try {
            opcache_compile_file($cacheFile);
            $count++;
        } catch (\Throwable $e) {}
    }
}
