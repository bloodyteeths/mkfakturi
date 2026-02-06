#!/usr/bin/env bash

# Railway E-Invoice & Banking Queue Worker Script
# Runs dedicated queue worker for e-invoice submissions and banking sync
set -e

echo "=== Railway E-Invoice & Banking Queue Worker ==="
echo "Starting at: $(date)"

# Parse DATABASE_URL and set DB environment variables
# (Same logic as railway-start.sh for consistency)
if [ ! -z "$MYSQL_URL" ]; then
    echo "Using Railway MYSQL_URL to parse connection..."
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
fi

# Set queue connection to redis for queue workers
export QUEUE_CONNECTION=redis

# Ensure Redis is available (required for queue workers)
if [ -z "$REDIS_HOST" ]; then
    echo "ERROR: REDIS_HOST not set. Queue workers require Redis."
    echo "Please add a Redis service to your Railway project."
    exit 1
fi

echo "Queue Configuration:"
echo "QUEUE_CONNECTION: $QUEUE_CONNECTION"
echo "REDIS_HOST: ${REDIS_HOST}"
echo "REDIS_PORT: ${REDIS_PORT:-6379}"

# Create storage directories
mkdir -p storage/logs
chmod -R 775 storage

# Clear any cached config
php artisan config:clear || true

echo "================================="
echo "Starting E-Invoice & Banking Queue Worker"
echo "Queues: einvoice,banking"
echo "Tries: 3"
echo "Timeout: 120 seconds"
echo "================================="

# Start the queue worker
# --queue=einvoice,banking: Process e-invoice and banking sync jobs
# --tries=3: Maximum 3 attempts per job
# --timeout=120: 120 second timeout per job
# --sleep=3: Sleep 3 seconds when no jobs available
# --max-jobs=100: Restart worker after 100 jobs (prevent memory leaks)
# --max-time=3600: Restart worker after 1 hour (prevent memory leaks)
php artisan queue:work redis \
    --queue=einvoice,banking \
    --tries=3 \
    --timeout=120 \
    --sleep=3 \
    --max-jobs=100 \
    --max-time=3600 \
    --verbose

# CLAUDE-CHECKPOINT
