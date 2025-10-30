#!/bin/bash
# ROADMAP-DEBUG.md Local Development Helper
# Boots PHP server + Vite without Docker for fast iteration

echo "🚀 Starting mkaccounting local development environment..."

# Check if .env.dev exists
if [ ! -f .env.dev ]; then
    echo "📝 Creating .env.dev from .env.example..."
    cp .env.example .env.dev
    
    # Configure for local SQLite development
    sed -i '' 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env.dev
    sed -i '' 's/MAIL_MAILER=smtp/MAIL_MAILER=log/' .env.dev
    sed -i '' 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/' .env.dev
    
    echo "✅ .env.dev configured with SQLite + log drivers"
fi

# Create SQLite database if it doesn't exist
if [ ! -f database/database.sqlite ]; then
    echo "📊 Creating SQLite database..."
    touch database/database.sqlite
fi

# Export environment
export APP_ENV=dev

echo "🔧 Starting PHP development server on :8000..."
php artisan serve --env=dev &
PHP_PID=$!

echo "⚡ Starting Vite development server..."
npm run dev &
VITE_PID=$!

echo "📝 Process IDs saved:"
echo "PHP Server: $PHP_PID"
echo "Vite: $VITE_PID"
echo "$PHP_PID" > .php_dev_pid
echo "$VITE_PID" > .vite_dev_pid

echo ""
echo "🎉 Local development environment ready!"
echo "📱 Application: http://localhost:8000"
echo "⚡ Vite HMR: http://localhost:5173"
echo ""
echo "🛑 Stop with: ./bin/dev_down.sh"

# Keep script running
wait