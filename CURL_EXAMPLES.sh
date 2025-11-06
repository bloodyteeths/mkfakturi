#!/bin/bash
#
# AI Document Analysis API - cURL Examples
#
# Usage: Update the variables below with your credentials and run individual commands
#

# Configuration
API_URL="http://localhost:8000/api/v1"
TOKEN="YOUR_AUTH_TOKEN_HERE"
COMPANY_ID="1"

# Helper function for pretty JSON output
function api_call() {
    echo "================================================"
    echo "Endpoint: $1"
    echo "================================================"
    echo ""
}

# ============================================
# 1. Analyze Document (General Purpose)
# ============================================
api_call "POST /ai/analyze-document"

# Example 1a: Analyze PDF with default question
curl -X POST "${API_URL}/ai/analyze-document" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@document.pdf" \
  | jq '.'

# Example 1b: Analyze image with custom question
curl -X POST "${API_URL}/ai/analyze-document" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@financial-report.jpg" \
  -F "question=What are the top 3 financial metrics in this document?" \
  | jq '.'

# Example 1c: Analyze PNG receipt
curl -X POST "${API_URL}/ai/analyze-document" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@receipt.png" \
  -F "question=Extract all line items and their prices" \
  | jq '.'

# ============================================
# 2. Analyze Receipt (Structured Extraction)
# ============================================
api_call "POST /ai/analyze-receipt"

# Example 2a: Analyze receipt JPG
curl -X POST "${API_URL}/ai/analyze-receipt" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@receipt.jpg" \
  | jq '.'

# Example 2b: Analyze receipt PDF
curl -X POST "${API_URL}/ai/analyze-receipt" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@receipt.pdf" \
  | jq '.'

# Example 2c: Save extracted data to file
curl -X POST "${API_URL}/ai/analyze-receipt" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@receipt.png" \
  | jq '.extracted_data' > receipt_data.json

# ============================================
# 3. Extract Invoice (Structured Extraction)
# ============================================
api_call "POST /ai/extract-invoice"

# Example 3a: Extract invoice from PDF
curl -X POST "${API_URL}/ai/extract-invoice" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@invoice.pdf" \
  | jq '.'

# Example 3b: Extract invoice from image
curl -X POST "${API_URL}/ai/extract-invoice" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@invoice.jpg" \
  | jq '.'

# Example 3c: Get only customer information
curl -X POST "${API_URL}/ai/extract-invoice" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@invoice.pdf" \
  | jq '.extracted_data | {customer_name, customer_email, customer_phone}'

# Example 3d: Get only financial totals
curl -X POST "${API_URL}/ai/extract-invoice" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@invoice.pdf" \
  | jq '.extracted_data | {subtotal, tax_total, total_amount}'

# ============================================
# 4. Get Monthly Trends
# ============================================
api_call "GET /ai/monthly-trends"

# Example 4a: Get last 12 months (default)
curl -X GET "${API_URL}/ai/monthly-trends" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  | jq '.'

# Example 4b: Get last 6 months
curl -X GET "${API_URL}/ai/monthly-trends?months=6" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  | jq '.'

# Example 4c: Get last 3 months only
curl -X GET "${API_URL}/ai/monthly-trends?months=3" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  | jq '.'

# Example 4d: Get revenue trend only
curl -X GET "${API_URL}/ai/monthly-trends?months=12" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  | jq '.trends[] | {month, revenue}'

# Example 4e: Get profit trend only
curl -X GET "${API_URL}/ai/monthly-trends?months=12" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  | jq '.trends[] | {month, profit}'

# Example 4f: Export to CSV
curl -X GET "${API_URL}/ai/monthly-trends?months=12" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  | jq -r '.trends[] | [.month, .revenue, .expenses, .profit, .invoice_count] | @csv' \
  > trends.csv

# ============================================
# Error Testing Examples
# ============================================

# Test with invalid file type
curl -X POST "${API_URL}/ai/analyze-document" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@document.docx" \
  | jq '.'
# Expected: 422 Unprocessable Entity

# Test without authentication
curl -X POST "${API_URL}/ai/analyze-document" \
  -F "file=@document.pdf" \
  | jq '.'
# Expected: 401 Unauthorized

# Test with missing company header
curl -X POST "${API_URL}/ai/analyze-document" \
  -H "Authorization: Bearer ${TOKEN}" \
  -F "file=@document.pdf" \
  | jq '.'
# Expected: 404 Company not found

# Test with invalid months parameter
curl -X GET "${API_URL}/ai/monthly-trends?months=30" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  | jq '.'
# Expected: 422 Validation Error

# ============================================
# Advanced Usage Examples
# ============================================

# Parallel processing - analyze multiple receipts
for receipt in receipt1.jpg receipt2.jpg receipt3.jpg; do
    curl -X POST "${API_URL}/ai/analyze-receipt" \
      -H "Authorization: Bearer ${TOKEN}" \
      -H "company: ${COMPANY_ID}" \
      -F "file=@${receipt}" \
      | jq '.extracted_data' > "${receipt%.jpg}_data.json" &
done
wait
echo "All receipts processed!"

# Batch invoice extraction with error handling
for invoice in invoices/*.pdf; do
    echo "Processing: $invoice"
    response=$(curl -s -X POST "${API_URL}/ai/extract-invoice" \
      -H "Authorization: Bearer ${TOKEN}" \
      -H "company: ${COMPANY_ID}" \
      -F "file=@${invoice}")

    if echo "$response" | jq -e '.success' > /dev/null; then
        echo "✓ Success: $invoice"
        echo "$response" | jq '.extracted_data' > "extracted_$(basename $invoice .pdf).json"
    else
        echo "✗ Failed: $invoice"
        echo "$response" | jq '.error'
    fi
done

# Monitor trends and alert on profit drop
previous_profit=0
curl -X GET "${API_URL}/ai/monthly-trends?months=6" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  | jq -r '.trends[] | [.month, .profit] | @tsv' \
  | while IFS=$'\t' read month profit; do
    echo "Month: $month, Profit: $profit"
    if (( $(echo "$profit < $previous_profit" | bc -l) )); then
        echo "⚠️  Alert: Profit decreased from $previous_profit to $profit"
    fi
    previous_profit=$profit
done

# ============================================
# Integration Examples
# ============================================

# Example: Auto-create expense from receipt
receipt_file="receipt.jpg"
echo "Analyzing receipt: $receipt_file"

# Step 1: Extract receipt data
receipt_data=$(curl -s -X POST "${API_URL}/ai/analyze-receipt" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@${receipt_file}" \
  | jq '.extracted_data')

# Step 2: Create expense from extracted data
vendor=$(echo "$receipt_data" | jq -r '.vendor')
amount=$(echo "$receipt_data" | jq -r '.total_amount')
date=$(echo "$receipt_data" | jq -r '.date')

echo "Creating expense: $vendor - $amount - $date"
# curl -X POST "${API_URL}/expenses" ...

# Example: Validate invoice totals
invoice_file="invoice.pdf"
echo "Validating invoice: $invoice_file"

# Extract invoice data
invoice_data=$(curl -s -X POST "${API_URL}/ai/extract-invoice" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "company: ${COMPANY_ID}" \
  -F "file=@${invoice_file}" \
  | jq '.extracted_data')

subtotal=$(echo "$invoice_data" | jq -r '.subtotal')
tax=$(echo "$invoice_data" | jq -r '.tax_total')
total=$(echo "$invoice_data" | jq -r '.total_amount')
calculated_total=$(echo "$subtotal + $tax" | bc)

if [ "$total" = "$calculated_total" ]; then
    echo "✓ Invoice totals are correct"
else
    echo "✗ Warning: Invoice totals don't match (Expected: $calculated_total, Got: $total)"
fi

# ============================================
# Postman Collection Export
# ============================================

# This script can be used to generate requests for Postman
# Save the output to a .json file and import into Postman

cat > postman_collection.json <<'EOF'
{
  "info": {
    "name": "AI Document Analysis API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8000/api/v1"
    },
    {
      "key": "token",
      "value": "YOUR_AUTH_TOKEN"
    },
    {
      "key": "company_id",
      "value": "1"
    }
  ],
  "item": [
    {
      "name": "Analyze Document",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          },
          {
            "key": "company",
            "value": "{{company_id}}"
          }
        ],
        "body": {
          "mode": "formdata",
          "formdata": [
            {
              "key": "file",
              "type": "file",
              "src": []
            },
            {
              "key": "question",
              "value": "Analyze this document",
              "type": "text"
            }
          ]
        },
        "url": {
          "raw": "{{base_url}}/ai/analyze-document",
          "host": ["{{base_url}}"],
          "path": ["ai", "analyze-document"]
        }
      }
    },
    {
      "name": "Analyze Receipt",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          },
          {
            "key": "company",
            "value": "{{company_id}}"
          }
        ],
        "body": {
          "mode": "formdata",
          "formdata": [
            {
              "key": "file",
              "type": "file",
              "src": []
            }
          ]
        },
        "url": {
          "raw": "{{base_url}}/ai/analyze-receipt",
          "host": ["{{base_url}}"],
          "path": ["ai", "analyze-receipt"]
        }
      }
    },
    {
      "name": "Extract Invoice",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          },
          {
            "key": "company",
            "value": "{{company_id}}"
          }
        ],
        "body": {
          "mode": "formdata",
          "formdata": [
            {
              "key": "file",
              "type": "file",
              "src": []
            }
          ]
        },
        "url": {
          "raw": "{{base_url}}/ai/extract-invoice",
          "host": ["{{base_url}}"],
          "path": ["ai", "extract-invoice"]
        }
      }
    },
    {
      "name": "Monthly Trends",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          },
          {
            "key": "company",
            "value": "{{company_id}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/ai/monthly-trends?months=12",
          "host": ["{{base_url}}"],
          "path": ["ai", "monthly-trends"],
          "query": [
            {
              "key": "months",
              "value": "12"
            }
          ]
        }
      }
    }
  ]
}
EOF

echo "Postman collection saved to postman_collection.json"
echo "Import this file into Postman to test the API"
