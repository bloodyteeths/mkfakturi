#!/usr/bin/env bash

# Railway startup script - uses Railway MySQL service variables
set -e

echo "=== Railway Startup Script ==="
echo "Environment variables check:"
echo "All environment variables (searching for MySQL related):"
env | sort | grep -iE "(DATABASE|DB_|MYSQL|SQL)" || echo "No database env vars found"
echo ""
echo "Checking specific variables:"
echo "MYSQL_URL=${MYSQL_URL:-NOT SET}"
echo "DATABASE_URL=${DATABASE_URL:-NOT SET}"
echo "MYSQLHOST=${MYSQLHOST:-NOT SET}"
echo "MYSQLPORT=${MYSQLPORT:-NOT SET}"
echo "MYSQLUSER=${MYSQLUSER:-NOT SET}"
echo "MYSQLDATABASE=${MYSQLDATABASE:-NOT SET}"

# Railway provides these MySQL variables from the MySQL service
# Map them to Laravel's expected variable names
if [ ! -z "$MYSQL_URL" ]; then
    echo "Using Railway MYSQL_URL to parse connection..."
    # Parse MYSQL_URL: mysql://user:pass@host:port/database
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

    echo "Parsed from MYSQL_URL:"
    echo "DB_HOST: $DB_HOST"
    echo "DB_PORT: $DB_PORT"
    echo "DB_DATABASE: $DB_DATABASE"
elif [ ! -z "$DATABASE_URL" ]; then
    echo "Parsing DATABASE_URL: $DATABASE_URL"

    # Parse DATABASE_URL if provided
    temp="${DATABASE_URL#*://}"
    userpass="${temp%%@*}"
    export DB_USERNAME="${userpass%%:*}"
    export DB_PASSWORD="${userpass#*:}"

    temp="${temp#*@}"
    hostport="${temp%%/*}"
    export DB_HOST="${hostport%%:*}"
    export DB_PORT="${hostport#*:}"
    export DB_DATABASE="${temp#*/}"
    export DB_CONNECTION=mysql
else
    echo "ERROR: No database configuration found!"
    echo "Expected either MYSQLHOST or DATABASE_URL"
    exit 1
fi

echo "Final DB config:"
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"

# Create a minimal .env file if it doesn't exist (installation wizard needs it)
if [ ! -f ".env" ]; then
    echo "Creating minimal .env file for installation wizard..."
    touch .env
    chmod 666 .env
fi

# Run Railway installation seeder if RAILWAY_AUTO_INSTALL is true
if [ "$RAILWAY_AUTO_INSTALL" = "true" ]; then
    echo "Checking if auto-install needed..."

    # Check if profile_complete is already set
    PROFILE_STATUS=$(php artisan tinker --execute="echo \App\Models\Setting::getSetting('profile_complete') ?? 'NOT_SET';" 2>/dev/null | tail -1)
    echo "Profile status: $PROFILE_STATUS"

    if [ "$PROFILE_STATUS" != "COMPLETED" ]; then
        echo "Running Railway auto-install seeder..."
        php artisan db:seed --class=RailwayInstallSeeder --force || echo "Install seeder failed"

        # Verify it worked
        PROFILE_STATUS=$(php artisan tinker --execute="echo \App\Models\Setting::getSetting('profile_complete') ?? 'STILL_NOT_SET';" 2>/dev/null | tail -1)
        echo "After seeder, profile status: $PROFILE_STATUS"

        # Double check by querying the database directly
        echo "Checking settings table directly..."
        php artisan tinker --execute="print_r(\App\Models\Setting::where('option', 'profile_complete')->first());" 2>/dev/null | grep -A 5 "Setting Object" || echo "No setting found in database"
    else
        echo "Already installed (profile_complete = COMPLETED), skipping..."
    fi
fi

# Create required storage directories
echo "Creating storage directories..."
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Force cache/queue to file-based (override any defaults)
export CACHE_STORE=file
export CACHE_DRIVER=file
export QUEUE_CONNECTION=sync
export BROADCAST_DRIVER=log
export REDIS_CLIENT=

# Clear any cached config that might have wrong values
echo "Clearing caches..."
rm -rf bootstrap/cache/*.php || true
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run migrations (continue even if some fail)
echo "Running migrations..."
php artisan migrate --force || echo "Some migrations failed, continuing..."

# Force set profile_complete if RAILWAY_SKIP_INSTALL is true
if [ "$RAILWAY_SKIP_INSTALL" = "true" ]; then
    echo "RAILWAY_SKIP_INSTALL enabled - forcing installation complete..."
    php artisan tinker --execute="\App\Models\Setting::setSetting('profile_complete', 'COMPLETED'); echo 'Set profile_complete to COMPLETED';" 2>/dev/null || echo "Failed to set profile_complete"

    # Verify it worked
    VERIFY=$(php artisan tinker --execute="echo \App\Models\Setting::getSetting('profile_complete') ?? 'NOT_SET';" 2>/dev/null | tail -1)
    echo "Verification - profile_complete is now: $VERIFY"
fi

# Seed database if RAILWAY_SEED_DB is set
if [ "$RAILWAY_SEED_DB" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force || echo "Seeding failed or already seeded"
fi

# Don't cache config in production - causes issues with environment variables
echo "Skipping config cache to allow dynamic environment variables..."
# php artisan config:cache
# php artisan route:cache

# Create storage link
echo "Creating storage link..."
php artisan storage:link || echo "Storage link already exists or failed"

# Show final environment check
echo "=== Final Environment Check ==="
echo "CACHE_STORE: $CACHE_STORE"
echo "CACHE_DRIVER: $CACHE_DRIVER"
echo "QUEUE_CONNECTION: $QUEUE_CONNECTION"
echo "SESSION_DRIVER: $SESSION_DRIVER"
echo "REDIS_CLIENT: $REDIS_CLIENT"
echo "BROADCAST_DRIVER: $BROADCAST_DRIVER"

# Start PHP server
echo "Starting PHP server on port $PORT..."
php -S 0.0.0.0:$PORT -t public 2>&1 | tee -a storage/logs/server.log
