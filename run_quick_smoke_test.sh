#!/bin/bash

# QA-ALL-01: Quick Smoke Test Suite
#
# Executes rapid validation tests (15 minutes)
# Use before deployments for quick validation
#
# Usage:
#   ./run_quick_smoke_test.sh
#
# Version: 1.0
# Date: 2025-11-17

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "\n${CYAN}=========================================${NC}"
echo -e "${CYAN}Facturino Quick Smoke Test Suite${NC}"
echo -e "${CYAN}=========================================${NC}\n"

START_TIME=$(date +%s)

# Test 1: ESLint
echo -e "${BLUE}[1/4]${NC} Running ESLint..."
npm run test || { echo -e "${RED}ESLint failed${NC}"; exit 1; }
echo -e "${GREEN}✓ ESLint passed${NC}\n"

# Test 2: Backend Tests
echo -e "${BLUE}[2/4]${NC} Running backend tests (parallel)..."
php artisan test --parallel || { echo -e "${RED}Backend tests failed${NC}"; exit 1; }
echo -e "${GREEN}✓ Backend tests passed${NC}\n"

# Test 3: E2E Smoke Test
echo -e "${BLUE}[3/4]${NC} Running E2E smoke test..."
npm run test:e2e || { echo -e "${RED}E2E smoke test failed${NC}"; exit 1; }
echo -e "${GREEN}✓ E2E smoke test passed${NC}\n"

# Test 4: Load Smoke Test
echo -e "${BLUE}[4/4]${NC} Running load smoke test..."
npm run load:smoke || { echo -e "${RED}Load smoke test failed (non-critical)${NC}"; }
echo -e "${GREEN}✓ Load smoke test completed${NC}\n"

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo -e "${CYAN}=========================================${NC}"
echo -e "${GREEN}✅ Quick Smoke Test Suite Complete${NC}"
echo -e "${CYAN}=========================================${NC}"
echo -e "Duration: ${DURATION} seconds"
echo -e "\nStatus: ${GREEN}READY FOR DEPLOYMENT${NC}\n"
