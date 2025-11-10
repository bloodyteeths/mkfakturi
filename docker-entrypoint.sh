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

# Create required directories
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache

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
fi

# Create storage symlink
php artisan storage:link || true

# Start supervisor
echo "Starting application services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
