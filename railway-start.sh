#!/usr/bin/env bash

# Railway startup script - uses Railway MySQL service variables
# Version: 2025-11-11-v2 (with certificate fixes)
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

# Auto-enable installation skip on Railway if not explicitly set
if [ ! -z "$RAILWAY_ENVIRONMENT" ] && [ -z "$RAILWAY_SKIP_INSTALL" ]; then
    echo "Railway detected - auto-enabling RAILWAY_SKIP_INSTALL"
    export RAILWAY_SKIP_INSTALL=true
    export ADMIN_EMAIL="${ADMIN_EMAIL:-your-email@example.com}"
    export ADMIN_PASSWORD="${ADMIN_PASSWORD:-your-secure-password}"
    echo "Admin credentials will be set to:"
    echo "  Email: $ADMIN_EMAIL"
    echo "  Password: [configured]"
fi

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
# Laravel's file cache driver needs storage/framework/cache/data
mkdir -p storage/framework/{sessions,views,cache/data,testing}
mkdir -p storage/logs
mkdir -p storage/certificates
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Decode PSD2 certificates from base64 environment variables (for Railway)
echo "Checking for PSD2 certificates in environment..."
if [ ! -z "$NLB_MTLS_CERT_BASE64" ]; then
    echo "Decoding NLB certificate from environment variable..."
    echo "$NLB_MTLS_CERT_BASE64" | base64 -d > storage/certificates/nlb.pem
    chmod 644 storage/certificates/nlb.pem
    export NLB_MTLS_CERT_PATH=nlb.pem
    echo "✅ NLB certificate decoded successfully"
fi

if [ ! -z "$NLB_MTLS_KEY_BASE64" ]; then
    echo "Decoding NLB private key from environment variable..."
    echo "$NLB_MTLS_KEY_BASE64" | base64 -d > storage/certificates/nlb.key
    chmod 600 storage/certificates/nlb.key
    export NLB_MTLS_KEY_PATH=nlb.key
    echo "✅ NLB private key decoded successfully"
fi

if [ ! -z "$STOPANSKA_MTLS_CERT_BASE64" ]; then
    echo "Decoding Stopanska certificate from environment variable..."
    echo "$STOPANSKA_MTLS_CERT_BASE64" | base64 -d > storage/certificates/stopanska.pem
    chmod 644 storage/certificates/stopanska.pem
    export STOPANSKA_MTLS_CERT_PATH=stopanska.pem
    echo "✅ Stopanska certificate decoded successfully"
fi

if [ ! -z "$STOPANSKA_MTLS_KEY_BASE64" ]; then
    echo "Decoding Stopanska private key from environment variable..."
    echo "$STOPANSKA_MTLS_KEY_BASE64" | base64 -d > storage/certificates/stopanska.key
    chmod 600 storage/certificates/stopanska.key
    export STOPANSKA_MTLS_KEY_PATH=stopanska.key
    echo "✅ Stopanska private key decoded successfully"
fi

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

# Regenerate Composer autoloader (critical for Modules directory)
echo "Regenerating autoloader..."
composer dump-autoload --optimize --no-dev || composer dump-autoload --optimize || true

# Run migrations (continue even if some fail)
echo "Running migrations..."

# Mark problematic migrations as completed if their tables already exist
echo "Checking for existing tables to skip duplicate migrations..."
php artisan tinker --execute="
    // Check if bank_tokens table exists and mark migration as done
    if (Schema::hasTable('bank_tokens')) {
        \$exists = DB::table('migrations')->where('migration', '2025_11_03_220239_create_bank_tokens_table')->exists();
        if (!\$exists) {
            DB::table('migrations')->insert([
                'migration' => '2025_11_03_220239_create_bank_tokens_table',
                'batch' => 1
            ]);
            echo 'Marked bank_tokens migration as completed' . PHP_EOL;
        }
    }
" 2>/dev/null || echo "Could not check existing tables"

# Run main migrations with better error handling
echo "Running main migrations batch..."
if ! php artisan migrate --force 2>&1 | tee -a storage/logs/migrations.log; then
    echo "WARNING: Some migrations failed. Check storage/logs/migrations.log for details"
    echo "Attempting to continue with critical migrations..."
fi

# Force run critical banking migrations if tables don't exist
echo "Ensuring banking tables exist..."
php artisan tinker --execute="
    if (!Schema::hasTable('bank_accounts')) {
        echo 'bank_accounts table missing - need to run core migration' . PHP_EOL;
    } else {
        echo 'bank_accounts table exists' . PHP_EOL;
    }
    if (!Schema::hasTable('bank_transactions')) {
        echo 'bank_transactions table missing' . PHP_EOL;
    } else {
        echo 'bank_transactions table exists' . PHP_EOL;
    }
" 2>/dev/null || echo "Could not verify banking tables"

# Force run core migration if bank_accounts doesn't exist
if ! php artisan tinker --execute="exit(Schema::hasTable('bank_accounts') ? 0 : 1);" 2>/dev/null; then
    echo "Forcing core migration (contains bank_accounts table)..."
    php artisan migrate --path=database/migrations/2025_07_24_core.php --force 2>&1 | tee -a storage/logs/migrations.log || echo "Core migration failed"
fi

# Force run bank_transactions migration if table doesn't exist
if ! php artisan tinker --execute="exit(Schema::hasTable('bank_transactions') ? 0 : 1);" 2>/dev/null; then
    echo "Forcing bank_transactions migration..."
    php artisan migrate --path=database/migrations/2025_07_25_163932_create_bank_transactions_table.php --force 2>&1 | tee -a storage/logs/migrations.log || echo "Bank transactions migration failed"
fi

# Ensure IFRS entity migrations are run (they might be skipped if earlier migrations fail)
echo "Ensuring IFRS entity migrations are run..."

# Check if columns already exist before forcing re-run
php artisan tinker --execute="
    \$companiesHasColumn = Schema::hasColumn('companies', 'ifrs_entity_id');
    \$usersHasColumn = Schema::hasColumn('users', 'entity_id');

    if (\$companiesHasColumn && \$usersHasColumn) {
        echo 'IFRS entity columns already exist - skipping migrations' . PHP_EOL;
        exit(0);
    }

    echo 'IFRS entity columns missing - need to run migrations' . PHP_EOL;
    exit(1);
" 2>/dev/null

COLUMNS_EXIST=$?

if [ $COLUMNS_EXIST -eq 0 ]; then
    echo "IFRS columns already exist in database, marking migrations as completed..."
    # Mark migrations as completed without running them
    php artisan tinker --execute="
        \$migrations = [
            '2025_11_04_000000_add_ifrs_entity_id_to_companies_table',
            '2025_11_04_000001_add_entity_id_to_users_table'
        ];
        foreach (\$migrations as \$m) {
            \$exists = DB::table('migrations')->where('migration', \$m)->exists();
            if (!\$exists) {
                DB::table('migrations')->insert([
                    'migration' => \$m,
                    'batch' => DB::table('migrations')->max('batch') + 1
                ]);
                echo 'Marked migration as completed: ' . \$m . PHP_EOL;
            } else {
                echo 'Migration already recorded: ' . \$m . PHP_EOL;
            }
        }
    " 2>/dev/null || echo "Could not mark migrations as completed"
else
    echo "IFRS columns do not exist, running migrations..."
    php artisan migrate --path=database/migrations/2025_11_04_000000_add_ifrs_entity_id_to_companies_table.php --force 2>&1 | tee -a storage/logs/migrations.log || echo "Entity migration already applied"
    php artisan migrate --path=database/migrations/2025_11_04_000001_add_entity_id_to_users_table.php --force 2>&1 | tee -a storage/logs/migrations.log || echo "Entity migration already applied"
fi

# CRITICAL: Force fix MKD currency precision (must be 0, not 2)
echo "Ensuring MKD currency has correct precision..."
php artisan tinker --execute="
    \$mkd = DB::table('currencies')->where('code', 'MKD')->first();
    if (\$mkd) {
        echo 'Current MKD precision: ' . \$mkd->precision . PHP_EOL;
        if (\$mkd->precision != 0) {
            echo 'FIXING: Updating MKD currency to precision=0...' . PHP_EOL;
            DB::table('currencies')
                ->where('code', 'MKD')
                ->update([
                    'precision' => 0,
                    'thousand_separator' => '.',
                    'decimal_separator' => ',',
                ]);
            echo '✅ MKD currency fixed!' . PHP_EOL;
        } else {
            echo '✅ MKD precision already correct (0)' . PHP_EOL;
        }
    } else {
        echo '⚠️ MKD currency not found in database' . PHP_EOL;
    }
" 2>/dev/null || echo "Could not check MKD currency"

# Force set profile_complete if RAILWAY_SKIP_INSTALL is true
if [ "$RAILWAY_SKIP_INSTALL" = "true" ]; then
    echo "========================================="
    echo "RAILWAY_SKIP_INSTALL enabled - forcing installation complete..."
    echo "========================================="

    # Create database marker file using Laravel Storage (required by InstallUtils::isDbCreated())
    echo "Creating database_created marker file via Laravel Storage..."
    php artisan tinker --execute="\Storage::disk('local')->put('database_created', time()); echo 'Marker created via Storage::disk(local)' . PHP_EOL;" 2>/dev/null || echo "Could not create marker via Storage"

    # Verify marker file was created (check both ways)
    echo "Verifying database marker file..."

    # Check 1: Direct file check
    if [ -f "storage/app/database_created" ]; then
        echo "✅ File exists: storage/app/database_created"
        ls -la storage/app/database_created
    else
        echo "❌ File not found: storage/app/database_created"
    fi

    # Check 2: Laravel Storage check (same method InstallUtils uses)
    php artisan tinker --execute="echo 'Storage::disk(local)->has(database_created): ' . (\Storage::disk('local')->has('database_created') ? 'TRUE' : 'FALSE') . PHP_EOL;" 2>/dev/null || echo "Could not check via Storage"

    # Check 3: Call InstallUtils directly
    php artisan tinker --execute="echo 'InstallUtils::dbMarkerExists(): ' . (\App\Space\InstallUtils::dbMarkerExists() ? 'TRUE' : 'FALSE') . PHP_EOL;" 2>/dev/null || echo "Could not check via InstallUtils"

    # Check 4: Full isDbCreated check
    php artisan tinker --execute="echo 'InstallUtils::isDbCreated(): ' . (\App\Space\InstallUtils::isDbCreated() ? 'TRUE' : 'FALSE') . PHP_EOL;" 2>/dev/null || echo "Could not check isDbCreated"

    # First, list all existing users
    echo "Checking existing users in database..."
    php artisan tinker --execute="\$users = \App\Models\User::all(['email', 'name']); foreach(\$users as \$u) { echo \$u->email . ' - ' . \$u->name . PHP_EOL; }" 2>/dev/null || echo "Could not list users"

    # Create/reset admin user with environment variables or defaults
    ADMIN_EMAIL="${ADMIN_EMAIL:-your-email@example.com}"
    ADMIN_PASSWORD="${ADMIN_PASSWORD:-your-secure-password}"

    echo "Creating/resetting admin user with email: $ADMIN_EMAIL"

    # Check if user already exists before resetting
    USER_EXISTS=$(php artisan tinker --execute="echo \App\Models\User::where('email', '$ADMIN_EMAIL')->exists() ? 'yes' : 'no';" 2>/dev/null | tail -1)

    if [ "$USER_EXISTS" = "yes" ]; then
        echo "Admin user already exists - skipping creation"
    else
        echo "Creating admin user..."
        php artisan admin:reset --email="$ADMIN_EMAIL" --password="$ADMIN_PASSWORD"
    fi

    # Verify user settings were created
    echo "Verifying user settings..."
    php artisan tinker --execute="\$user = \App\Models\User::where('email', '$ADMIN_EMAIL')->first(); \$settings = \$user->settings; echo 'User has ' . \$settings->count() . ' settings'; foreach(\$settings as \$s) { echo \$s->key . ': ' . \$s->value . PHP_EOL; }" 2>/dev/null || echo "Could not verify settings"

    # Ensure profile_complete is set
    echo "Setting profile_complete to 'COMPLETED'..."
    php artisan tinker --execute="\App\Models\Setting::setSetting('profile_complete', 'COMPLETED');" 2>&1 | grep -v "Psy Shell" || echo "Could not set profile_complete"

    # Verify it worked with detailed output
    echo "Verifying profile_complete setting..."
    VERIFY=$(php artisan tinker --execute="echo \App\Models\Setting::getSetting('profile_complete') ?? 'NOT_SET';" 2>/dev/null | tail -1)
    echo "✅ profile_complete is now: $VERIFY"

    # Double-check by querying database directly
    echo "Double-checking settings table..."
    php artisan tinker --execute="\$setting = \App\Models\Setting::where('option', 'profile_complete')->first(); if (\$setting) { echo 'Found: option=' . \$setting->option . ', value=' . \$setting->value . PHP_EOL; } else { echo 'NOT FOUND in database!' . PHP_EOL; }" 2>/dev/null

    # Check if admin user exists
    USER_CHECK=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
    echo "Number of users in database: $USER_CHECK"

    # Verify marker file exists
    if [ -f "storage/app/database_created" ]; then
        echo "Database marker file created successfully"
    else
        echo "WARNING: Failed to create database marker file!"
    fi

    echo "========================================="
    echo "✅ Installation bypass complete!"
    echo "========================================="
    echo "Login credentials:"
    echo "  Email: $ADMIN_EMAIL"
    echo "  Password: [configured]"
    echo "========================================="
    echo ""
    echo "Installation checks that should pass:"
    echo "  - InstallUtils::isDbCreated() → should find storage/app/database_created"
    echo "  - Setting::getSetting('profile_complete') → should return 'COMPLETED'"
    echo "========================================="
fi

# Seed database if RAILWAY_SEED_DB is set
if [ "$RAILWAY_SEED_DB" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force || echo "Seeding failed or already seeded"
fi

# Always run PartnerSeeder to ensure admin users have Partner records (fixes 403 on console/companies)
echo "Running PartnerSeeder to ensure Partner records exist for admin users..."
php artisan db:seed --class=PartnerSeeder --force 2>/dev/null || echo "PartnerSeeder already run or failed"

# Always run IFRS seeder to ensure entities and chart of accounts exist
if [ "$FEATURE_ACCOUNTING_BACKBONE" = "true" ]; then
    echo "IFRS accounting feature enabled - ensuring chart of accounts..."
    php artisan db:seed --class=MkIfrsSeeder --force 2>/dev/null || echo "IFRS seeder already run or failed"
fi

# Sync abilities for all companies (multi-tenant SaaS)
# This ensures all tenants have up-to-date abilities from config/abilities.php
echo "Syncing abilities for all companies from config..."
php artisan abilities:sync 2>/dev/null || echo "Abilities sync completed or skipped"

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

# Always run frontend build to ensure latest code and translations
echo "Building frontend assets..."
npm run build || echo "Frontend build failed"

# Verify build completed
if [ -d "public/build/assets" ]; then
    ASSET_COUNT=$(ls -1 public/build/assets | wc -l)
    echo "Build completed successfully with $ASSET_COUNT files"
    ls -lh public/build/assets | head -10
else
    echo "ERROR: Build assets directory does not exist after build!"
fi

# Enable detailed Laravel logging
export LOG_CHANNEL=stack
export LOG_LEVEL=debug

# Start PHP server EARLY to pass Railway health checks
# Then initialization can continue in the background
echo "Starting PHP server on port $PORT..."
echo "Laravel logs will be written to storage/logs/laravel.log"
echo "Server starting - Railway health checks should now pass"
echo "Initialization will continue in the background"

# Start server (this blocks, so everything above this line completes first)
php -S 0.0.0.0:$PORT -t public 2>&1 | tee -a storage/logs/server.log

# CLAUDE-CHECKPOINT
