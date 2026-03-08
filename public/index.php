<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Match production error_reporting (Dockerfile: E_ALL & ~E_DEPRECATED & ~E_STRICT).
// IFRS package classes emit E_DEPRECATED during autoloading, which corrupts binary
// PDF responses when display_errors is On (local dev). Production php.ini suppresses
// these, but local dev servers need this explicit setting before autoload runs.
// Note: E_STRICT (2048) is itself deprecated in PHP 8.4, so we use the numeric value.
error_reporting(E_ALL & ~E_DEPRECATED & ~2048);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
