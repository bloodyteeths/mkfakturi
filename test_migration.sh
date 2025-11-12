#!/bin/bash

# Test Migration Wizard Upload Endpoints
# Generated for testing purposes

BASE_URL="http://127.0.0.1:8000/api/v1"
AUTH_TOKEN="5|u21BAxzChhFb67HhVU39tKDnYTr6SgyvjKHCtyql36f1b854"
COMPANY_ID="1"
FIXTURE_DIR="/Users/tamsar/Downloads/mkaccounting/tests/fixtures/migration"

echo "==================================="
echo "TEST 1: Happy Path Customer Upload"
echo "==================================="
curl -X POST "${BASE_URL}/migration/upload" \
  -H "Authorization: Bearer ${AUTH_TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -H "Accept: application/json" \
  -F "file=@${FIXTURE_DIR}/01_happy_path_customers.csv" \
  -F "type=customers" \
  -F "source=manual" \
  -s | python3 -m json.tool 2>/dev/null || cat

echo -e "\n\n"
