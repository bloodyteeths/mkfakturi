#!/bin/bash
# Staging Healthcheck Script for AC-08→AC-18 + FIX PATCH #5
# Railway Staging Environment Verification

set -e

# Configuration
STAGING_URL="${STAGING_URL:-https://web-production-5f60.up.railway.app}"
ADMIN_TOKEN="${STAGING_ADMIN_TOKEN:-}"
PARTNER_TOKEN="${STAGING_PARTNER_TOKEN:-}"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🚀 Starting Railway Staging Healthchecks..."
echo "Target: $STAGING_URL"
echo ""

# Test counter
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to test endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local description=$3
    local expected_status=$4
    local auth_token=$5

    TOTAL_TESTS=$((TOTAL_TESTS + 1))

    echo -n "Testing: $description... "

    if [ -z "$auth_token" ]; then
        response=$(curl -s -w "\n%{http_code}" -X "$method" "$STAGING_URL$endpoint" 2>&1)
    else
        response=$(curl -s -w "\n%{http_code}" -H "Authorization: Bearer $auth_token" -H "Accept: application/json" -X "$method" "$STAGING_URL$endpoint" 2>&1)
    fi

    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')

    if [ "$http_code" == "$expected_status" ]; then
        echo -e "${GREEN}✅ PASS${NC} (HTTP $http_code)"
        PASSED_TESTS=$((PASSED_TESTS + 1))

        # Validate JSON response
        if echo "$body" | jq empty 2>/dev/null; then
            echo "  ✓ Valid JSON response"
        else
            echo "  ⚠️  Response is not JSON (expected for some endpoints)"
        fi

        return 0
    else
        echo -e "${RED}❌ FAIL${NC} (HTTP $http_code, expected $expected_status)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        echo "  Response: $body"
        return 1
    fi
}

# Test 1: Application Health Endpoint
echo "=== Test 1: Application Health ==="
test_endpoint "GET" "/health" "Health check endpoint" "200" ""
echo ""

# Test 2: API Base Endpoint
echo "=== Test 2: API Base Endpoint ==="
test_endpoint "GET" "/api/v1" "API base endpoint" "200" ""
echo ""

# Test 3: Admin Partners Endpoint (requires auth)
echo "=== Test 3: Admin Partners Endpoint ==="
if [ -z "$ADMIN_TOKEN" ]; then
    echo -e "${YELLOW}⚠️  SKIPPED: STAGING_ADMIN_TOKEN not set${NC}"
    echo "  Set token: export STAGING_ADMIN_TOKEN='your-token'"
else
    test_endpoint "GET" "/api/v1/admin/partners" "List partners" "200" "$ADMIN_TOKEN"
fi
echo ""

# Test 4: Partner Invitations Endpoint (requires auth)
echo "=== Test 4: Partner Invitations Endpoint ==="
if [ -z "$ADMIN_TOKEN" ]; then
    echo -e "${YELLOW}⚠️  SKIPPED: STAGING_ADMIN_TOKEN not set${NC}"
else
    test_endpoint "GET" "/api/v1/admin/invitations/pending-for-partner" "Pending partner invitations" "200" "$ADMIN_TOKEN"
fi
echo ""

# Test 5: Referral Network Graph Endpoint (requires auth)
echo "=== Test 5: Referral Network Graph ==="
if [ -z "$ADMIN_TOKEN" ]; then
    echo -e "${YELLOW}⚠️  SKIPPED: STAGING_ADMIN_TOKEN not set${NC}"
else
    test_endpoint "GET" "/api/v1/admin/referral-network/graph?page=1&limit=10" "Network graph with pagination" "200" "$ADMIN_TOKEN"
fi
echo ""

# Test 6: Database Connection (via Laravel)
echo "=== Test 6: Database Connection ==="
if command -v railway &> /dev/null; then
    echo "Testing database connection via Railway CLI..."
    railway run php artisan tinker --execute="echo 'Database: ' . DB::connection()->getDatabaseName() . PHP_EOL;" 2>&1 | grep -q "Database:" && \
        echo -e "${GREEN}✅ PASS${NC} - Database connection successful" || \
        echo -e "${RED}❌ FAIL${NC} - Database connection failed"
else
    echo -e "${YELLOW}⚠️  SKIPPED: Railway CLI not available${NC}"
fi
echo ""

# Test 7: Partner Referrals Table Exists
echo "=== Test 7: Partner Referrals Table ==="
if command -v railway &> /dev/null; then
    echo "Verifying partner_referrals table..."
    railway run php artisan tinker --execute="echo Schema::hasTable('partner_referrals') ? 'EXISTS' : 'MISSING';" 2>&1 | grep -q "EXISTS" && \
        echo -e "${GREEN}✅ PASS${NC} - partner_referrals table exists" || \
        echo -e "${RED}❌ FAIL${NC} - partner_referrals table missing"
else
    echo -e "${YELLOW}⚠️  SKIPPED: Railway CLI not available${NC}"
fi
echo ""

# Test 8: CommissionService FIX PATCH #5
echo "=== Test 8: FIX PATCH #5 Verification ==="
if command -v railway &> /dev/null; then
    echo "Checking CommissionService for FIX PATCH #5..."
    railway run php artisan tinker --execute="
        \$code = file_get_contents(base_path('app/Services/CommissionService.php'));
        echo strpos(\$code, 'partner_referrals') !== false ? 'DEPLOYED' : 'MISSING';
    " 2>&1 | grep -q "DEPLOYED" && \
        echo -e "${GREEN}✅ PASS${NC} - FIX PATCH #5 deployed" || \
        echo -e "${RED}❌ FAIL${NC} - FIX PATCH #5 missing"
else
    echo -e "${YELLOW}⚠️  SKIPPED: Railway CLI not available${NC}"
fi
echo ""

# Summary
echo "========================================="
echo "📊 HEALTHCHECK SUMMARY"
echo "========================================="
echo "Total Tests: $TOTAL_TESTS"
echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
echo -e "Failed: ${RED}$FAILED_TESTS${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}❌ Some tests failed. Review output above.${NC}"
    exit 1
fi
