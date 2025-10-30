#!/usr/bin/env bash

# Railway startup script - parses DATABASE_URL if provided
set -e

echo "=== Railway Startup Script ==="
echo "Environment variables check:"
env | grep -E "(DATABASE_URL|DB_|MYSQL)" || echo "No database env vars found"

# Unset any problematic DB variables that might contain URLs
unset DB_DATABASE_URL
unset DATABASE_PRIVATE_URL
unset DATABASE_PUBLIC_URL

# If DATABASE_URL exists, parse it
if [ ! -z "$DATABASE_URL" ]; then
    echo "Parsing DATABASE_URL: $DATABASE_URL"

    # Use a more robust parsing method
    # Extract protocol
    proto="$(echo $DATABASE_URL | cut -d: -f1)"

    # Remove protocol
    temp="${DATABASE_URL#*://}"

    # Extract username and password
    userpass="${temp%%@*}"
    export DB_USERNAME="${userpass%%:*}"
    export DB_PASSWORD="${userpass#*:}"

    # Extract host, port, and database
    temp="${temp#*@}"
    hostport="${temp%%/*}"
    export DB_HOST="${hostport%%:*}"
    export DB_PORT="${hostport#*:}"

    # Extract database name
    export DB_DATABASE="${temp#*/}"

    echo "Parsed DB_HOST: $DB_HOST"
    echo "Parsed DB_PORT: $DB_PORT"
    echo "Parsed DB_DATABASE: $DB_DATABASE"
    echo "Parsed DB_USERNAME: $DB_USERNAME"
    echo "Database configured from URL"

    # Unset DATABASE_URL to prevent Laravel from using it
    unset DATABASE_URL
fi

# Ensure DB_CONNECTION is set
export DB_CONNECTION=mysql

echo "Final DB config:"
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"

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
