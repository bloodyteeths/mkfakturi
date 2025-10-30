#!/usr/bin/env bash

# Railway startup script - uses Railway MySQL service variables
set -e

echo "=== Railway Startup Script ==="
echo "Environment variables check:"
env | grep -E "(DATABASE|DB_|MYSQL)" || echo "No database env vars found"

# Railway provides these MySQL variables from the MySQL service
# Map them to Laravel's expected variable names
if [ ! -z "$MYSQLHOST" ]; then
    echo "Using Railway MySQL service variables"
    export DB_CONNECTION=mysql
    export DB_HOST="$MYSQLHOST"
    export DB_PORT="$MYSQLPORT"
    # Railway uses both MYSQL_DATABASE and MYSQLDATABASE - prioritize the one that has a value
    export DB_DATABASE="${MYSQLDATABASE:-$MYSQL_DATABASE}"
    export DB_USERNAME="$MYSQLUSER"
    export DB_PASSWORD="$MYSQLPASSWORD"
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

# Create .env file if it doesn't exist (Railway needs this for installation wizard)
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cat > .env << EOF
APP_NAME="${APP_NAME}"
APP_ENV="${APP_ENV}"
APP_KEY="${APP_KEY}"
APP_DEBUG="${APP_DEBUG}"
APP_URL="${APP_URL}"

DB_CONNECTION="${DB_CONNECTION}"
DB_HOST="${DB_HOST}"
DB_PORT="${DB_PORT}"
DB_DATABASE="${DB_DATABASE}"
DB_USERNAME="${DB_USERNAME}"
DB_PASSWORD="${DB_PASSWORD}"

CACHE_STORE="${CACHE_STORE}"
SESSION_DRIVER="${SESSION_DRIVER}"
QUEUE_CONNECTION="${QUEUE_CONNECTION}"
BROADCAST_DRIVER="${BROADCAST_DRIVER}"
EOF
    chmod 664 .env
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

# Run migrations
echo "Running migrations..."
php artisan migrate --force

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
