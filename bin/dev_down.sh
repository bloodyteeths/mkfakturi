#!/bin/bash
# ROADMAP-DEBUG.md Local Development Helper
# Cleanup development processes

echo "🛑 Stopping mkaccounting local development environment..."

# Stop PHP server
if [ -f .php_dev_pid ]; then
    PHP_PID=$(cat .php_dev_pid)
    if kill -0 $PHP_PID 2>/dev/null; then
        echo "🔧 Stopping PHP server (PID: $PHP_PID)..."
        kill $PHP_PID
    fi
    rm .php_dev_pid
fi

# Stop Vite
if [ -f .vite_dev_pid ]; then
    VITE_PID=$(cat .vite_dev_pid)
    if kill -0 $VITE_PID 2>/dev/null; then
        echo "⚡ Stopping Vite server (PID: $VITE_PID)..."
        kill $VITE_PID
    fi
    rm .vite_dev_pid
fi

# Cleanup any remaining processes
echo "🧹 Cleaning up remaining processes..."
pkill -f "php artisan serve"
pkill -f "vite"

echo "✅ Local development environment stopped!"