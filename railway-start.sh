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
    export DB_DATABASE="$MYSQLDATABASE"
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

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Cache configuration
echo "Caching config..."
php artisan config:cache
php artisan route:cache

# Start PHP server
echo "Starting PHP server on port $PORT..."
php -S 0.0.0.0:$PORT -t public
