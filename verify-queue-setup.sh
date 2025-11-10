#!/usr/bin/env bash

# Queue Configuration Verification Script
# Checks that all queue components are properly configured

echo "==================================="
echo "Queue Setup Verification"
echo "==================================="
echo ""

ERRORS=0
WARNINGS=0

# Check 1: Redis availability
echo "1. Checking Redis availability..."
if command -v redis-cli &> /dev/null; then
    if redis-cli ping &> /dev/null; then
        echo "   ✅ Redis is running and accessible"
    else
        echo "   ❌ Redis command found but server not responding"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo "   ⚠️  redis-cli not found (install: brew install redis)"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# Check 2: Queue configuration file
echo "2. Checking queue configuration..."
if [ -f "config/queue.php" ]; then
    if grep -q "einvoice" config/queue.php; then
        echo "   ✅ E-invoice queue configured in config/queue.php"
    else
        echo "   ❌ E-invoice queue not found in config/queue.php"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo "   ❌ config/queue.php not found"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Check 3: Queue worker scripts
echo "3. Checking queue worker scripts..."
SCRIPTS=(
    "railway-queue-worker.sh"
    "start-queue-worker.sh"
)

for script in "${SCRIPTS[@]}"; do
    if [ -f "$script" ]; then
        if [ -x "$script" ]; then
            echo "   ✅ $script exists and is executable"
        else
            echo "   ⚠️  $script exists but is not executable"
            echo "      Run: chmod +x $script"
            WARNINGS=$((WARNINGS + 1))
        fi
    else
        echo "   ❌ $script not found"
        ERRORS=$((ERRORS + 1))
    fi
done
echo ""

# Check 4: Job class exists
echo "4. Checking SubmitEInvoiceJob class..."
if [ -f "app/Jobs/SubmitEInvoiceJob.php" ]; then
    if grep -q "class SubmitEInvoiceJob" app/Jobs/SubmitEInvoiceJob.php; then
        echo "   ✅ SubmitEInvoiceJob class found"

        # Check if it implements ShouldQueue
        if grep -q "implements ShouldQueue" app/Jobs/SubmitEInvoiceJob.php; then
            echo "   ✅ Job implements ShouldQueue interface"
        else
            echo "   ❌ Job does not implement ShouldQueue"
            ERRORS=$((ERRORS + 1))
        fi

        # Check queue property
        if grep -q 'public \$queue = .einvoice.' app/Jobs/SubmitEInvoiceJob.php; then
            echo "   ✅ Job configured for 'einvoice' queue"
        else
            echo "   ⚠️  Job queue property not found or incorrect"
            WARNINGS=$((WARNINGS + 1))
        fi
    else
        echo "   ❌ SubmitEInvoiceJob class not found in file"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo "   ❌ app/Jobs/SubmitEInvoiceJob.php not found"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Check 5: Environment configuration
echo "5. Checking environment configuration..."
if [ -f ".env" ]; then
    QUEUE_CONN=$(grep "^QUEUE_CONNECTION=" .env | cut -d '=' -f2)
    if [ -n "$QUEUE_CONN" ]; then
        echo "   ✅ QUEUE_CONNECTION is set to: $QUEUE_CONN"
        if [ "$QUEUE_CONN" = "sync" ]; then
            echo "      ⚠️  Using 'sync' driver (jobs run immediately, not queued)"
            echo "         For production, set QUEUE_CONNECTION=redis"
            WARNINGS=$((WARNINGS + 1))
        fi
    else
        echo "   ❌ QUEUE_CONNECTION not set in .env"
        ERRORS=$((ERRORS + 1))
    fi

    REDIS_HOST=$(grep "^REDIS_HOST=" .env | cut -d '=' -f2)
    if [ -n "$REDIS_HOST" ]; then
        echo "   ✅ REDIS_HOST is set to: $REDIS_HOST"
    else
        echo "   ⚠️  REDIS_HOST not set in .env"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo "   ⚠️  .env file not found (expected during initial setup)"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# Check 6: Documentation files
echo "6. Checking documentation..."
DOCS=(
    "QUEUE_WORKER_SETUP.md"
    "QUEUE_COMMANDS.md"
)

for doc in "${DOCS[@]}"; do
    if [ -f "$doc" ]; then
        echo "   ✅ $doc exists"
    else
        echo "   ❌ $doc not found"
        ERRORS=$((ERRORS + 1))
    fi
done
echo ""

# Check 7: Supervisor configuration (optional)
echo "7. Checking supervisor configuration..."
if [ -f "supervisor.conf" ]; then
    if grep -q "einvoice-queue-worker" supervisor.conf; then
        echo "   ✅ Supervisor configuration exists with einvoice worker"
    else
        echo "   ⚠️  Supervisor config exists but einvoice worker not configured"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo "   ℹ️  supervisor.conf not found (optional)"
fi
echo ""

# Check 8: Laravel Artisan available
echo "8. Checking Laravel installation..."
if [ -f "artisan" ]; then
    echo "   ✅ Laravel artisan file found"

    # Try to run artisan (if PHP is available)
    if command -v php &> /dev/null; then
        if php artisan --version &> /dev/null; then
            echo "   ✅ Laravel artisan is working"
        else
            echo "   ⚠️  Laravel artisan exists but not working (may need vendor install)"
            WARNINGS=$((WARNINGS + 1))
        fi
    fi
else
    echo "   ❌ Laravel artisan not found"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Summary
echo "==================================="
echo "Verification Summary"
echo "==================================="
echo "Errors: $ERRORS"
echo "Warnings: $WARNINGS"
echo ""

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo "✅ All checks passed! Queue system is properly configured."
    echo ""
    echo "Next steps:"
    echo "1. Start Redis: redis-server"
    echo "2. Update .env: QUEUE_CONNECTION=redis"
    echo "3. Start queue worker: ./start-queue-worker.sh"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo "⚠️  Configuration complete with $WARNINGS warning(s)."
    echo "   Review warnings above before deploying to production."
    exit 0
else
    echo "❌ Configuration incomplete. Please fix $ERRORS error(s) above."
    exit 1
fi

# CLAUDE-CHECKPOINT
