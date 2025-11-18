#!/bin/bash
# Railway Deployment Script for AC-08→AC-18 + FIX PATCH #5
# Automated deployment pipeline for partner management system

set -e  # Exit on error

echo "🚀 Starting Railway deployment..."

# Step 1: Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Step 2: Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Step 3: Build frontend assets
echo "🎨 Building frontend assets..."
npm ci
npm run build

# Step 4: Clear and rebuild caches
echo "🧹 Clearing caches..."
php artisan optimize:clear

echo "🔧 Rebuilding optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 5: Restart queue workers (if applicable)
echo "⚙️  Restarting queue workers..."
php artisan queue:restart || true

# Step 6: Healthcheck
echo "🏥 Running post-deploy healthcheck..."
php artisan tinker --execute="echo 'Database: ' . DB::connection()->getDatabaseName() . PHP_EOL;"

echo "✅ Railway deployment completed successfully!"
echo "📊 Commit: $(git rev-parse --short HEAD)"
echo "🕒 Deployed at: $(date)"
