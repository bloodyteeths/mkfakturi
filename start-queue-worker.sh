#!/usr/bin/env bash

# Local Development Queue Worker Script
# Simplified script for running queue worker during local development

echo "=== Starting E-Invoice & Banking Queue Worker (Local Development) ==="
echo ""

# Check if Redis is available
if ! command -v redis-cli &> /dev/null; then
    echo "⚠️  Warning: redis-cli not found. Make sure Redis is installed."
    echo "   Install with: brew install redis (macOS) or apt-get install redis (Linux)"
    echo ""
fi

# Check if Redis is running
if command -v redis-cli &> /dev/null; then
    if ! redis-cli ping &> /dev/null; then
        echo "❌ Error: Redis is not running."
        echo "   Start Redis with: redis-server"
        echo "   Or: brew services start redis (macOS)"
        exit 1
    else
        echo "✅ Redis is running"
    fi
fi

# Check .env configuration
if [ -f ".env" ]; then
    QUEUE_CONN=$(grep QUEUE_CONNECTION .env | cut -d '=' -f2)
    if [ "$QUEUE_CONN" = "sync" ]; then
        echo "⚠️  Warning: QUEUE_CONNECTION is set to 'sync' in .env"
        echo "   Change to 'redis' to use queue workers:"
        echo "   QUEUE_CONNECTION=redis"
        echo ""
    fi
fi

echo "Starting queue worker..."
echo "Queues: einvoice,banking"
echo "Press Ctrl+C to stop"
echo ""

# Start the queue worker with verbose output
php artisan queue:work redis \
    --queue=einvoice,banking \
    --tries=3 \
    --timeout=120 \
    --sleep=3 \
    --verbose

# CLAUDE-CHECKPOINT
