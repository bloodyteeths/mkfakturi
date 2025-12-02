#!/bin/bash
set -e

# Set umask to ensure new files are group-writable
umask 002

echo "=== Application Startup ==="

# Railway-specific: Parse MySQL connection from environment
if [ ! -z "$MYSQL_URL" ]; then
    echo "Railway detected: Parsing MYSQL_URL..."
    temp="${MYSQL_URL#*://}"
    userpass="${temp%%@*}"
    export DB_USERNAME="${userpass%%:*}"
    export DB_PASSWORD="${userpass#*:}"
    temp="${temp#*@}"
    hostport="${temp%%/*}"
    export DB_HOST="${hostport%%:*}"
    export DB_PORT="${hostport#*:}"
    export DB_DATABASE="${temp#*/}"
    export DB_CONNECTION=mysql
    echo "DB configured from MYSQL_URL"
elif [ ! -z "$MYSQLHOST" ]; then
    echo "Railway detected: Using individual MySQL variables..."
    export DB_HOST="${MYSQLHOST}"
    export DB_PORT="${MYSQLPORT:-3306}"
    export DB_DATABASE="${MYSQLDATABASE}"
    export DB_USERNAME="${MYSQLUSER:-root}"
    export DB_PASSWORD="${MYSQLPASSWORD}"
    export DB_CONNECTION=mysql
    echo "DB configured from Railway variables"
fi

# Wait for database to be ready (Railway)
if [ ! -z "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:$DB_PORT..."
    for i in {1..60}; do
        # Use PHP PDO to check database connection (more reliable than mysqladmin)
        if php -r "try { new PDO('mysql:host='.\$_SERVER['DB_HOST'].';port='.\$_SERVER['DB_PORT'], \$_SERVER['DB_USERNAME'], \$_SERVER['DB_PASSWORD'], [PDO::ATTR_TIMEOUT => 5]); echo 'OK'; exit(0); } catch(Exception \$e) { exit(1); }" 2>/dev/null; then
            echo "✅ Database is ready!"
            break
        fi
        echo "Waiting for database... ($i/60)"
        sleep 2
    done

    # Final check - if we couldn't connect after all attempts, show error
    if [ $i -eq 60 ]; then
        echo "⚠️ Warning: Database connection timeout after 60 attempts (2 minutes)"
        echo "Proceeding anyway - migrations may fail"
    fi
fi

# Ensure proper .env setup
if [ ! -f "/var/www/html/.env" ]; then
    echo "Creating .env from example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Generate application key if missing
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Railway environment variables take precedence over .env
# DB_* variables are already exported at the top of this script

# Create required directories and fix permissions
# Laravel's file cache driver needs storage/framework/cache/data
mkdir -p storage/framework/{sessions,views,cache/data,testing}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Ensure www user owns all application files
chown -R www:www /var/www/html/storage
chown -R www:www /var/www/html/bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "✅ Permissions set for www user"

# Nginx is configured to listen on both 80 and 8080 in nginx.conf.
echo "Nginx configured to listen on ports 80 and 8080"

# Clear caches
echo "Clearing caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Show feature flags status (for debugging)
echo "=== Feature Flags ==="
echo "FACTURINO_STOCK_V1_ENABLED env: ${FACTURINO_STOCK_V1_ENABLED:-NOT SET}"
php artisan tinker --execute="echo 'Stock feature from config: ' . (config('features.stock.enabled') ? 'ENABLED' : 'DISABLED') . PHP_EOL;" 2>/dev/null || echo "Could not check stock feature"

# Run migrations (Railway)
if [ "$RAILWAY_ENVIRONMENT" != "" ]; then
    echo "Running migrations..."
    php artisan migrate --force || echo "Migrations failed or already applied"

    # Check if installation already complete (like commit 09c2afc)
    PROFILE_STATUS=$(php artisan tinker --execute="echo \App\Models\Setting::getSetting('profile_complete') ?? 'NOT_SET';" 2>/dev/null | tail -1)
    echo "Profile status: $PROFILE_STATUS"

    # Check if database marker exists
    MARKER_EXISTS=$(php artisan tinker --execute="echo \Storage::disk('local')->exists('database_created') ? 'YES' : 'NO';" 2>/dev/null | tail -1)
    echo "Database marker exists: $MARKER_EXISTS"

    if [ "$PROFILE_STATUS" != "COMPLETED" ]; then
        echo "========================================="
        echo "Setting up installation skip for Railway"
        echo "========================================="

        # Create database marker using Laravel Storage
        php artisan tinker --execute="\Storage::disk('local')->put('database_created', time());" 2>/dev/null || echo "Could not create marker"

        # Set profile_complete to COMPLETED
        php artisan tinker --execute="\App\Models\Setting::setSetting('profile_complete', 'COMPLETED');" 2>/dev/null || echo "Could not set profile_complete"

        # Verify
        VERIFY=$(php artisan tinker --execute="echo \App\Models\Setting::getSetting('profile_complete') ?? 'NOT_SET';" 2>/dev/null | tail -1)
        echo "✅ profile_complete set to: $VERIFY"
        echo "========================================="
    else
        echo "Already installed (profile_complete = COMPLETED)"

        # Ensure database marker exists even if profile_complete is already set
        if [ "$MARKER_EXISTS" != "YES" ]; then
            echo "⚠️  Database marker missing, creating it now..."
            php artisan tinker --execute="\Storage::disk('local')->put('database_created', time());" 2>/dev/null || echo "Could not create marker"

            # Verify marker creation
            VERIFY_MARKER=$(php artisan tinker --execute="echo \Storage::disk('local')->exists('database_created') ? 'YES' : 'NO';" 2>/dev/null | tail -1)
            echo "✅ Database marker created: $VERIFY_MARKER"
        else
            echo "✅ Installation complete, all markers in place"
        fi
    fi
fi

# Create storage symlink
php artisan storage:link || true

# Ensure favicon is served directly by nginx to avoid PHP fallback
if [ ! -f public/favicon.ico ] && [ -f public/favicons/favicon.ico ]; then
    ln -sf favicons/favicon.ico public/favicon.ico || true
fi

# Verify Laravel is ready before starting services
echo "Verifying Laravel application..."
php artisan --version || echo "Warning: Laravel not responding"

# Test a simple PHP route to catch any bootstrap errors
echo "Testing PHP bootstrap..."
php artisan route:list | head -5 || echo "Warning: Route list failed"

# Check if we can access the database
echo "Testing database connection..."
php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB OK'; } catch (\Exception \$e) { echo 'DB FAILED: ' . \$e->getMessage(); }" 2>&1 | tail -3

# Enable verbose PHP error logging
echo "Enabling verbose PHP errors for debugging..."
echo "display_errors = On" >> /usr/local/etc/php/conf.d/custom.ini
echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/custom.ini
echo "log_errors = On" >> /usr/local/etc/php/conf.d/custom.ini
echo "error_log = /var/www/html/storage/logs/php-errors.log" >> /usr/local/etc/php/conf.d/custom.ini

# Test the home route directly to catch the error
echo "Testing home route..."
php artisan tinker --execute="
try {
    \$request = \Illuminate\Http\Request::create('/', 'GET');
    \$response = app()->handle(\$request);
    echo 'Home route status: ' . \$response->getStatusCode() . PHP_EOL;
} catch (\Exception \$e) {
    echo 'HOME ROUTE ERROR: ' . \$e->getMessage() . PHP_EOL;
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . PHP_EOL;
    echo 'Stack trace: ' . PHP_EOL . \$e->getTraceAsString() . PHP_EOL;
}
" 2>&1 | grep -A 20 "HOME ROUTE ERROR" || echo "Home route test passed"

# Validate configurations before starting services
echo "Validating nginx configuration..."
nginx -t 2>&1 || echo "Warning: nginx config validation failed"

echo "Validating PHP-FPM configuration..."
php-fpm -t 2>&1 || echo "Warning: PHP-FPM config validation failed"

# Fix ALL permissions before starting services (CRITICAL)
# All previous artisan commands run as root, so we need to fix ownership
# before starting nginx/php-fpm which run as www user
echo "Setting final permissions before starting services..."
chown -R www:www /var/www/html/storage || echo "WARN: Failed to chown storage"
chown -R www:www /var/www/html/bootstrap/cache || echo "WARN: Failed to chown bootstrap/cache"
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || echo "WARN: Failed to chmod"
echo "✅ Final permissions set for www user"

# Start supervisor in background (nginx, php-fpm, scheduler)
# Queue workers are disabled by default in supervisord.conf
echo "Starting application services in background..."
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf &
SUPERVISOR_PID=$!

# Wait for services to start
echo "Waiting for services to be ready..."
sleep 5

# Test if php-fpm is listening
echo "Checking if PHP-FPM is listening on port 9000..."
netstat -ln | grep :9000 || echo "Warning: PHP-FPM not listening on port 9000"

# Test if nginx is listening
echo "Checking if nginx is listening on port ${PORT:-80}..."
netstat -ln | grep :${PORT:-80} || echo "Warning: nginx not listening"

# Test actual HTTP request through nginx/php-fpm stack
echo "Testing HTTP request through nginx/php-fpm..."
HTTP_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" http://127.0.0.1:${PORT:-80}/health 2>&1)
HTTP_CODE=$(echo "$HTTP_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Health check passed (HTTP $HTTP_CODE)"
else
    echo "❌ Health check failed (HTTP $HTTP_CODE)"
    echo "Response: $HTTP_RESPONSE"

    # Show recent PHP-FPM error logs
    echo "=== PHP-FPM Error Logs ==="
    tail -50 /var/www/html/storage/logs/php-fpm-error.log 2>/dev/null || echo "No php-fpm error log found"

    # Show recent Laravel logs
    echo "=== Laravel Error Logs ==="
    tail -50 /var/www/html/storage/logs/laravel.log 2>/dev/null || echo "No laravel log found"

    # Show nginx error logs
    echo "=== Nginx Error Logs ==="
    tail -50 /var/log/nginx/error.log 2>/dev/null || echo "No nginx error log found"
fi

# Test home route as well
echo "Testing home route through HTTP..."
HOME_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" http://127.0.0.1:${PORT:-80}/ 2>&1)
HOME_CODE=$(echo "$HOME_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)

if [ "$HOME_CODE" = "200" ] || [ "$HOME_CODE" = "302" ]; then
    echo "✅ Home route accessible (HTTP $HOME_CODE)"
else
    echo "❌ Home route failed (HTTP $HOME_CODE)"
    echo "Response: $HOME_RESPONSE"
fi

# Bring supervisor to foreground
echo "All startup checks complete. Supervisor running with PID $SUPERVISOR_PID"
echo "Application ready to serve requests."
wait $SUPERVISOR_PID
