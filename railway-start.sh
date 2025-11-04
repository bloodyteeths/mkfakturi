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
elif [ ! -z "$MYSQLHOST" ]; then
    echo "Using Railway individual MySQL variables..."
    export DB_HOST="${MYSQLHOST}"
    export DB_PORT="${MYSQLPORT:-3306}"
    export DB_DATABASE="${MYSQLDATABASE}"
    export DB_USERNAME="${MYSQLUSER:-root}"
    export DB_PASSWORD="${MYSQLPASSWORD}"
    export DB_CONNECTION=mysql

    echo "Using Railway MySQL variables:"
    echo "DB_HOST: $DB_HOST"
    echo "DB_PORT: $DB_PORT"
    echo "DB_DATABASE: $DB_DATABASE"
    echo "DB_USERNAME: $DB_USERNAME"
else
    echo "ERROR: No database configuration found!"
    echo "Expected either MYSQL_URL, DATABASE_URL, or MYSQLHOST"
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

# Set session and Sanctum configuration for Railway domain
if [ ! -z "$RAILWAY_PUBLIC_DOMAIN" ]; then
    echo "Configuring session for Railway domain: $RAILWAY_PUBLIC_DOMAIN"
    export SESSION_DOMAIN=".${RAILWAY_PUBLIC_DOMAIN}"
    export SANCTUM_STATEFUL_DOMAINS="${RAILWAY_PUBLIC_DOMAIN},localhost,127.0.0.1"
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi

# Clear any cached config that might have wrong values
echo "Clearing caches..."
rm -rf bootstrap/cache/*.php || true
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Optimize application (rebuild routes, views, etc. without caching config)
echo "Optimizing application..."
php artisan optimize || true

# Run migrations (continue even if some fail)
echo "Running migrations..."
php artisan migrate --force || echo "Some migrations failed, continuing..."

# Ensure IFRS entity migrations are run (they might be skipped if earlier migrations fail)
echo "Ensuring IFRS entity migrations are run..."
php artisan migrate --path=database/migrations/2025_11_04_000000_add_ifrs_entity_id_to_companies_table.php --force 2>/dev/null || echo "Already migrated or failed"
php artisan migrate --path=database/migrations/2025_11_04_000001_add_entity_id_to_users_table.php --force 2>/dev/null || echo "Already migrated or failed"

# Force set profile_complete if RAILWAY_SKIP_INSTALL is true
if [ "$RAILWAY_SKIP_INSTALL" = "true" ]; then
    echo "RAILWAY_SKIP_INSTALL enabled - forcing installation complete..."

    # Create database marker file (required by InstallUtils::isDbCreated())
    echo "Creating database_created marker file..."
    mkdir -p storage/app
    echo "$(date +%s)" > storage/app/database_created
    chmod 664 storage/app/database_created

    # First, list all existing users
    echo "Checking existing users in database..."
    php artisan tinker --execute="\$users = \App\Models\User::all(['email', 'name']); foreach(\$users as \$u) { echo \$u->email . ' - ' . \$u->name . PHP_EOL; }" 2>/dev/null || echo "Could not list users"

    # Create/reset admin user with environment variables or defaults
    ADMIN_EMAIL="${ADMIN_EMAIL:-admin@facturino.mk}"
    ADMIN_PASSWORD="${ADMIN_PASSWORD:-password}"

    echo "Creating/resetting admin user with email: $ADMIN_EMAIL"
    php artisan admin:reset --email="$ADMIN_EMAIL" --password="$ADMIN_PASSWORD"

    # Verify user settings were created
    echo "Verifying user settings..."
    php artisan tinker --execute="\$user = \App\Models\User::where('email', '$ADMIN_EMAIL')->first(); \$settings = \$user->settings; echo 'User has ' . \$settings->count() . ' settings'; foreach(\$settings as \$s) { echo \$s->key . ': ' . \$s->value . PHP_EOL; }" 2>/dev/null || echo "Could not verify settings"

    # Ensure profile_complete is set
    php artisan tinker --execute="\App\Models\Setting::setSetting('profile_complete', 'COMPLETED');" 2>/dev/null || echo "Could not set profile_complete"

    # Verify it worked
    VERIFY=$(php artisan tinker --execute="echo \App\Models\Setting::getSetting('profile_complete') ?? 'NOT_SET';" 2>/dev/null | tail -1)
    echo "Verification - profile_complete is now: $VERIFY"

    # Check if admin user exists
    USER_CHECK=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
    echo "Number of users in database: $USER_CHECK"

    # Verify marker file exists
    if [ -f "storage/app/database_created" ]; then
        echo "Database marker file created successfully"
    else
        echo "WARNING: Failed to create database marker file!"
    fi

    echo "================================="
    echo "Installation bypass complete!"
    echo "Login credentials:"
    echo "  Email: $ADMIN_EMAIL"
    echo "  Password: $ADMIN_PASSWORD"
    echo "================================="
fi

# Seed database if RAILWAY_SEED_DB is set
if [ "$RAILWAY_SEED_DB" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force || echo "Seeding failed or already seeded"
fi

# Always run IFRS seeder to ensure entities and chart of accounts exist
if [ "$FEATURE_ACCOUNTING_BACKBONE" = "true" ]; then
    echo "IFRS accounting feature enabled - ensuring chart of accounts..."
    php artisan db:seed --class=MkIfrsSeeder --force 2>/dev/null || echo "IFRS seeder already run or failed"
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

# Check if build assets exist
echo "Checking frontend build assets..."
if [ -d "public/build/assets" ]; then
    ASSET_COUNT=$(ls -1 public/build/assets | wc -l)
    echo "Build assets directory exists with $ASSET_COUNT files"
    ls -lh public/build/assets | head -10
else
    echo "WARNING: Build assets directory does not exist!"
    echo "Running frontend build now..."
    npm run build || echo "Frontend build failed"
fi

# Enable detailed Laravel logging
export LOG_CHANNEL=stack
export LOG_LEVEL=debug

# Start PHP server
echo "Starting PHP server on port $PORT..."
echo "Laravel logs will be written to storage/logs/laravel.log"
php -S 0.0.0.0:$PORT -t public 2>&1 | tee -a storage/logs/server.log
