<?php
// Simple PHP test file to verify PHP-FPM is working
// Access at: /test.php

header('Content-Type: text/plain');

echo "=== PHP Test Page ===\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown' . "\n\n";

echo "=== Environment Variables ===\n";
echo "APP_ENV: " . getenv('APP_ENV') . "\n";
echo "DB_CONNECTION: " . getenv('DB_CONNECTION') . "\n";
echo "DB_HOST: " . getenv('DB_HOST') . "\n";
echo "DB_DATABASE: " . getenv('DB_DATABASE') . "\n";
echo "RAILWAY_ENVIRONMENT: " . getenv('RAILWAY_ENVIRONMENT') . "\n\n";

echo "=== File Permissions ===\n";
$storagePath = dirname(__DIR__) . '/storage';
$bootstrapCache = dirname(__DIR__) . '/bootstrap/cache';

echo "Storage dir exists: " . (is_dir($storagePath) ? 'YES' : 'NO') . "\n";
echo "Storage writable: " . (is_writable($storagePath) ? 'YES' : 'NO') . "\n";
echo "Bootstrap cache exists: " . (is_dir($bootstrapCache) ? 'YES' : 'NO') . "\n";
echo "Bootstrap cache writable: " . (is_writable($bootstrapCache) ? 'YES' : 'NO') . "\n\n";

echo "=== Laravel Files ===\n";
$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
$envFile = dirname(__DIR__) . '/.env';

echo "vendor/autoload.php exists: " . (file_exists($vendorAutoload) ? 'YES' : 'NO') . "\n";
echo ".env file exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "\n\n";

if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
    echo "✅ Autoloader loaded successfully\n\n";

    echo "=== Testing Laravel Bootstrap ===\n";
    try {
        $app = require_once dirname(__DIR__) . '/bootstrap/app.php';
        echo "✅ Laravel app bootstrapped\n";

        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        echo "✅ Console kernel created\n";
    } catch (\Throwable $e) {
        echo "❌ Laravel bootstrap failed:\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
