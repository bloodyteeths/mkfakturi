#!/bin/bash

# TST-REST-01: Newman API Test Runner
# 
# This script runs the Postman collection using Newman in a Docker environment
# as required by ROADMAP-FINAL.md Section B - TST-REST-01
#
# Usage:
#   ./run_api_tests.sh [environment]
#
# Environments:
#   - local (default): http://localhost:8000
#   - staging: http://staging.invoiceshelf.com
#   - production: http://invoiceshelf.com
#
# Requirements:
# - Docker must be running
# - Application must be accessible at the specified URL
# - Test database should be seeded with sample data
#
# Exit codes:
#   0 - All tests passed
#   1 - Some tests failed
#   2 - Setup/configuration error

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COLLECTION_FILE="$SCRIPT_DIR/postman_collection.json"
ENVIRONMENT="${1:-local}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULTS_DIR="$SCRIPT_DIR/test_results"
RESULTS_FILE="$RESULTS_DIR/api_test_results_$TIMESTAMP.json"
HTML_REPORT="$RESULTS_DIR/api_test_report_$TIMESTAMP.html"

# Environment URLs
declare -A ENVIRONMENT_URLS=(
    ["local"]="http://localhost:8000"
    ["staging"]="http://staging.invoiceshelf.com"
    ["production"]="http://invoiceshelf.com"
)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Validate inputs
if [[ ! -f "$COLLECTION_FILE" ]]; then
    log_error "Postman collection file not found: $COLLECTION_FILE"
    exit 2
fi

if [[ ! -v ENVIRONMENT_URLS[$ENVIRONMENT] ]]; then
    log_error "Invalid environment: $ENVIRONMENT"
    log_info "Valid environments: ${!ENVIRONMENT_URLS[@]}"
    exit 2
fi

BASE_URL="${ENVIRONMENT_URLS[$ENVIRONMENT]}"

# Create results directory
mkdir -p "$RESULTS_DIR"

log_info "Starting API Test Suite"
log_info "Environment: $ENVIRONMENT"
log_info "Base URL: $BASE_URL"
log_info "Collection: $COLLECTION_FILE"
log_info "Results: $RESULTS_FILE"

# Check if Docker is running
if ! docker info >/dev/null 2>&1; then
    log_error "Docker is not running. Please start Docker and try again."
    exit 2
fi

# Check if base URL is accessible
log_info "Checking if application is accessible..."
if curl -s -f "$BASE_URL/api/health" >/dev/null 2>&1; then
    log_success "Application is accessible at $BASE_URL"
else
    log_warning "Application health check failed at $BASE_URL/api/health"
    log_warning "Proceeding with tests anyway..."
fi

# Run Newman in Docker
log_info "Running API tests with Newman..."

NEWMAN_CMD="docker run --rm \
    -v \"$SCRIPT_DIR:/workspace\" \
    -w /workspace \
    postman/newman:latest \
    run postman_collection.json \
    --environment-var \"base_url=$BASE_URL\" \
    --reporters cli,json,html \
    --reporter-json-export \"test_results/api_test_results_$TIMESTAMP.json\" \
    --reporter-html-export \"test_results/api_test_report_$TIMESTAMP.html\" \
    --timeout-request 30000 \
    --timeout-script 5000 \
    --delay-request 1000 \
    --bail \
    --color on"

# Execute Newman
if eval $NEWMAN_CMD; then
    log_success "API tests completed successfully!"
    TEST_EXIT_CODE=0
else
    log_error "Some API tests failed!"
    TEST_EXIT_CODE=1
fi

# Display results summary if JSON report exists
if [[ -f "$RESULTS_FILE" ]]; then
    log_info "Test Results Summary:"
    
    # Extract key metrics from JSON results using basic shell tools
    TOTAL_TESTS=$(grep -o '"total":[0-9]*' "$RESULTS_FILE" | head -1 | cut -d':' -f2)
    FAILED_TESTS=$(grep -o '"failed":[0-9]*' "$RESULTS_FILE" | head -1 | cut -d':' -f2)
    PASSED_TESTS=$((TOTAL_TESTS - FAILED_TESTS))
    
    echo "  Total Tests: $TOTAL_TESTS"
    echo "  Passed: $PASSED_TESTS"
    echo "  Failed: $FAILED_TESTS"
    
    if [[ -f "$HTML_REPORT" ]]; then
        log_info "Detailed HTML report: $HTML_REPORT"
    fi
    
    # Show failed tests if any
    if [[ $FAILED_TESTS -gt 0 ]]; then
        log_warning "Failed test details available in: $RESULTS_FILE"
    fi
else
    log_warning "Results file not found. Check Newman output above for details."
fi

# Clean up old results (keep last 10)
log_info "Cleaning up old test results..."
find "$RESULTS_DIR" -name "api_test_results_*.json" -type f | sort -r | tail -n +11 | xargs -r rm
find "$RESULTS_DIR" -name "api_test_report_*.html" -type f | sort -r | tail -n +11 | xargs -r rm

# Final status
if [[ $TEST_EXIT_CODE -eq 0 ]]; then
    log_success "✅ TST-REST-01 API Test Suite PASSED"
    echo ""
    echo "All major API endpoints tested successfully:"
    echo "  ✓ Authentication (Admin & Partner)"
    echo "  ✓ Customer Management (CRUD)"
    echo "  ✓ Invoice Management (Create, Send, Export)"
    echo "  ✓ Payment Processing (Standard & CPAY)"
    echo "  ✓ Partner/Accountant Console"
    echo "  ✓ XML Export & UBL Compliance"
    echo "  ✓ System Health & Monitoring"
    echo ""
    echo "Platform ready for production API usage!"
else
    log_error "❌ TST-REST-01 API Test Suite FAILED"
    echo ""
    echo "Some tests failed. Please review the results and fix issues before deployment."
    echo "Common issues to check:"
    echo "  - Database seeding completed"
    echo "  - All services running (app, database, cache)"
    echo "  - Network connectivity"
    echo "  - Environment configuration"
fi

exit $TEST_EXIT_CODE

