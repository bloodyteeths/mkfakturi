#!/bin/bash

# QA-ALL-01: Facturino Comprehensive Test Execution Script
#
# This script executes the complete test suite as documented in
# QA-ALL-01_COMPREHENSIVE_TEST_EXECUTION_PLAN.md
#
# Usage:
#   ./run_full_test_suite.sh [quick|full]
#
# Options:
#   quick - Run quick smoke tests only (15 min)
#   full  - Run complete test suite (2-4 hours) [default]
#
# Prerequisites:
#   - Staging environment running
#   - Database seeded
#   - npm install completed
#   - Playwright browsers installed
#
# Version: 1.0
# Date: 2025-11-17

set -e  # Exit on error

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEST_MODE="${1:-full}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULTS_DIR="$SCRIPT_DIR/test_results"
LOG_FILE="$RESULTS_DIR/test_execution_${TIMESTAMP}.log"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Counters
TOTAL_PHASES=0
PASSED_PHASES=0
FAILED_PHASES=0

# Helper functions
log_header() {
    echo -e "\n${CYAN}=========================================${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${CYAN}=========================================${NC}\n"
}

log_phase() {
    echo -e "\n${MAGENTA}[$1]${NC} $2"
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
    ((PASSED_PHASES++))
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
    ((FAILED_PHASES++))
}

run_phase() {
    local phase_name=$1
    local command=$2
    local continue_on_error=${3:-false}

    ((TOTAL_PHASES++))
    log_phase "$TOTAL_PHASES" "$phase_name"

    if eval "$command" 2>&1 | tee -a "$LOG_FILE"; then
        log_success "$phase_name completed successfully"
        return 0
    else
        log_error "$phase_name failed"
        if [ "$continue_on_error" = "true" ]; then
            log_warning "Continuing despite failure..."
            return 0
        else
            return 1
        fi
    fi
}

# Create results directory
mkdir -p "$RESULTS_DIR"

# Start logging
log_header "Facturino QA-ALL-01 Test Suite Execution"
log_info "Test Mode: $TEST_MODE"
log_info "Started at: $(date)"
log_info "Results directory: $RESULTS_DIR"
log_info "Log file: $LOG_FILE"

# Check prerequisites
log_phase "PRE" "Checking prerequisites..."

if [ ! -d "node_modules" ]; then
    log_error "Node modules not installed. Run: npm install"
    exit 1
fi
log_success "Node modules found"

if [ ! -d "vendor" ]; then
    log_error "Composer dependencies not installed. Run: composer install"
    exit 1
fi
log_success "Composer dependencies found"

# Verify environment
if ! php artisan --version > /dev/null 2>&1; then
    log_error "Laravel application not accessible"
    exit 1
fi
log_success "Laravel application accessible"

# Main execution
if [ "$TEST_MODE" = "quick" ]; then
    log_header "Quick Smoke Test Suite (15 minutes)"

    # Quick smoke tests
    run_phase "Linting - ESLint" "npm run test" true
    run_phase "Backend Tests" "php artisan test --parallel" false
    run_phase "E2E Smoke Test" "npm run test:e2e" false
    run_phase "Load Smoke Test" "npm run load:smoke" true

else
    log_header "Complete Test Suite Execution (2-4 hours)"

    # Phase 1: Code Quality & Static Analysis
    log_header "Phase 1: Code Quality & Static Analysis"
    run_phase "PHP Linting (Pint)" "vendor/bin/pint --test" true
    run_phase "JavaScript Linting (ESLint)" "npm run test" true

    # Phase 2: Backend Tests
    log_header "Phase 2: Backend Unit & Feature Tests"
    run_phase "Unit Tests" "php artisan test --testsuite=Unit --parallel" false
    run_phase "Feature Tests" "php artisan test --testsuite=Feature --parallel" false
    run_phase "All Backend Tests (with coverage)" "php artisan test --parallel" false

    # Phase 3: API Tests
    log_header "Phase 3: API Tests (Newman/Postman)"
    if [ -f "./run_api_tests.sh" ]; then
        run_phase "API Test Suite" "./run_api_tests.sh staging" false
    else
        log_warning "run_api_tests.sh not found, skipping API tests"
    fi

    # Phase 4: E2E Tests
    log_header "Phase 4: End-to-End Tests (Cypress)"
    run_phase "E2E Smoke Test" "npm run test:e2e" false
    run_phase "E2E Full Happy Path (TST-UI-01 - CRITICAL)" "npm run test:full" false
    run_phase "Complete E2E Suite" "npx cypress run --headless" false

    # Phase 5: Visual Regression Tests
    log_header "Phase 5: Visual Regression Tests (Playwright)"
    if command -v playwright &> /dev/null; then
        run_phase "Visual Regression - Chromium" "npx playwright test --project=chromium" true
        run_phase "Visual Regression - Firefox" "npx playwright test --project=firefox" true
        run_phase "Visual Regression - WebKit" "npx playwright test --project=webkit" true
        run_phase "Visual Regression - Mobile" "npx playwright test --project='Mobile Chrome'" true
    else
        log_warning "Playwright not found. Run: npm run playwright:install"
        log_warning "Skipping visual regression tests"
    fi

    # Phase 6: Load & Performance Tests
    log_header "Phase 6: Load & Performance Tests (Artillery)"
    run_phase "Load Test - Smoke (2 min)" "npm run load:smoke" true
    run_phase "Load Test - Basic (10 min)" "npm run load:basic" false
    run_phase "Load Test - Stress (15 min)" "npm run load:stress" true
    run_phase "Load Test - Spike (8 min)" "npm run load:spike" true
    run_phase "Load Test - Critical Endpoints (12 min)" "npm run load:critical" true
fi

# Summary
log_header "Test Execution Summary"

echo "Started:  $(head -1 $LOG_FILE | grep -oP '\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}' || date)"
echo "Finished: $(date)"
echo ""
echo "Total Phases:  $TOTAL_PHASES"
echo "Passed:        $PASSED_PHASES"
echo "Failed:        $FAILED_PHASES"
echo ""

if [ $FAILED_PHASES -eq 0 ]; then
    log_success "ALL TESTS PASSED!"
    echo ""
    echo -e "${GREEN}✅ GO FOR PRODUCTION${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Review test results in: $RESULTS_DIR"
    echo "2. Complete manual testing checklist"
    echo "3. Document results using template in QA-ALL-01_COMPREHENSIVE_TEST_EXECUTION_PLAN.md"
    echo "4. Make final Go/No-Go decision"
    exit 0
else
    log_error "SOME TESTS FAILED!"
    echo ""
    echo -e "${RED}❌ NO-GO - Issues must be resolved${NC}"
    echo ""
    echo "Failed phases: $FAILED_PHASES"
    echo ""
    echo "Next steps:"
    echo "1. Review failures in: $LOG_FILE"
    echo "2. Check detailed test results in: $RESULTS_DIR"
    echo "3. Fix issues and re-run tests"
    echo "4. Consult troubleshooting guide in QA-ALL-01_COMPREHENSIVE_TEST_EXECUTION_PLAN.md"
    exit 1
fi
