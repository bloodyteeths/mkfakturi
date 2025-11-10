#!/bin/bash
set -e

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
    for i in {1..30}; do
        if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; then
            echo "Database is ready!"
            break
        fi
        echo "Waiting for database... ($i/30)"
        sleep 2
    done
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
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Ensure www user owns all application files
chown -R www:www /var/www/html/storage
chown -R www:www /var/www/html/bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "✅ Permissions set for www user"

# Configure nginx to listen on Railway's assigned PORT
NGINX_PORT=${PORT:-80}
if [ "$NGINX_PORT" != "80" ]; then
    echo "Configuring nginx to listen on port $NGINX_PORT"
    sed -i "s/listen 80;/listen ${NGINX_PORT};/" /etc/nginx/nginx.conf
else
    echo "Using default nginx port 80"
fi

# Clear caches
echo "Clearing caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

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

# Verify Laravel is ready before starting services
echo "Verifying Laravel application..."
php artisan --version || echo "Warning: Laravel not responding"

# Start supervisor (nginx, php-fpm, scheduler)
# Queue workers are disabled by default in supervisord.conf
echo "Starting application services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
