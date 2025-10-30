#!/bin/bash

# Railway startup script - parses DATABASE_URL if provided
set -e

# If DATABASE_URL exists, parse it
if [ ! -z "$DATABASE_URL" ]; then
    echo "Parsing DATABASE_URL..."

    # Parse the URL: mysql://user:pass@host:port/database
    # Extract components using parameter expansion
    proto="$(echo $DATABASE_URL | grep :// | sed -e's,^\(.*://\).*,\1,g')"
    url="$(echo ${DATABASE_URL/$proto/})"

    # Extract user and password
    userpass="$(echo $url | grep @ | cut -d@ -f1)"
    DB_USERNAME="$(echo $userpass | grep : | cut -d: -f1)"
    DB_PASSWORD="$(echo $userpass | grep : | cut -d: -f2)"

    # Extract host and port
    hostport="$(echo ${url/$userpass@/} | cut -d/ -f1)"
    DB_HOST="$(echo $hostport | cut -d: -f1)"
    DB_PORT="$(echo $hostport | cut -d: -f2)"

    # Extract database name
    DB_DATABASE="$(echo $url | grep / | cut -d/ -f2- | cut -d? -f1)"

    export DB_HOST
    export DB_PORT
    export DB_DATABASE
    export DB_USERNAME
    export DB_PASSWORD

    echo "Database configured from URL"
fi

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache

# Start PHP server
php -S 0.0.0.0:$PORT -t public
