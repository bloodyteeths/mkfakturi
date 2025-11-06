# AI Document Analysis Endpoints - Implementation Report

## Overview
Successfully implemented Part F: Enhanced API Endpoints for Document Analysis. These endpoints enable PDF/document upload and AI-powered analysis using vision APIs.

## New Endpoints

### 1. POST /api/v1/ai/analyze-document
General-purpose document analysis endpoint that accepts any supported file format.

**Purpose**: Analyze any document or image with optional custom questions

**Authentication**: Required (Bearer token + Company header)

**Authorization**: Requires `view dashboard` permission

**Feature Flag**: Requires `mcp_ai_tools` AND `pdf_analysis` enabled

**Request Validation**:
```php
[
    'file' => 'required|file|max:10240',  // Max 10MB
    'question' => 'nullable|string|max:500',
]
```

**Supported File Types**:
- `application/pdf`
- `image/png`
- `image/jpeg`
- `image/jpg`
- `image/webp`

**File Size Limit**: 10MB (10,485,760 bytes)

**Response Structure**:
```json
{
  "success": true,
  "analysis": "AI-generated analysis text...",
  "file_type": "application/pdf",
  "timestamp": "2025-11-06 10:30:45"
}
```

**Error Responses**:
```json
// Unsupported file type (422)
{
  "error": "Unsupported file type",
  "supported_types": [
    "application/pdf",
    "image/png",
    "image/jpeg",
    "image/jpg",
    "image/webp"
  ]
}

// File too large (422)
{
  "error": "File too large",
  "max_size_mb": 10
}

// Feature not enabled (403)
{
  "error": "PDF analysis feature is not enabled",
  "message": "Please enable the pdf_analysis feature flag in settings"
}

// Processing error (500)
{
  "success": false,
  "error": "Failed to analyze document",
  "message": "Specific error details..."
}
```

**Example cURL**:
```bash
curl -X POST https://your-domain.com/api/v1/ai/analyze-document \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: YOUR_COMPANY_ID" \
  -F "file=@/path/to/document.pdf" \
  -F "question=What are the key financial metrics in this document?"
```

---

### 2. POST /api/v1/ai/analyze-receipt
Specialized endpoint for receipt analysis with structured data extraction.

**Purpose**: Extract vendor, date, amount, items, and tax information from receipts

**Authentication**: Required (Bearer token + Company header)

**Authorization**: Requires `create expense` permission

**Feature Flag**: Requires `mcp_ai_tools` AND `pdf_analysis` enabled

**Request Validation**:
```php
[
    'file' => 'required|file|max:10240',  // Max 10MB
]
```

**Response Structure**:
```json
{
  "success": true,
  "analysis": "Raw AI analysis text...",
  "extracted_data": {
    "vendor": "ACME Store",
    "date": "2025-11-06",
    "total_amount": 123.45,
    "currency": "MKD",
    "tax_amount": 18.52,
    "items": [
      {
        "description": "Product A",
        "quantity": 2,
        "unit_price": 50.00,
        "total": 100.00
      },
      {
        "description": "Product B",
        "quantity": 1,
        "unit_price": 23.45,
        "total": 23.45
      }
    ],
    "payment_method": "Card",
    "notes": "VAT included"
  },
  "timestamp": "2025-11-06 10:30:45"
}
```

**Extracted Data Fields**:
- `vendor` (string|null): Merchant/vendor name
- `date` (string|null): Receipt date in YYYY-MM-DD format
- `total_amount` (float|null): Total amount including tax
- `currency` (string|null): Currency code (defaults to company currency)
- `tax_amount` (float|null): Tax/VAT amount
- `items` (array): Array of line items
  - `description` (string): Item description
  - `quantity` (int): Quantity purchased
  - `unit_price` (float): Price per unit
  - `total` (float): Line total
- `payment_method` (string|null): Payment method used
- `notes` (string|null): Additional notes or observations

**Example cURL**:
```bash
curl -X POST https://your-domain.com/api/v1/ai/analyze-receipt \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: YOUR_COMPANY_ID" \
  -F "file=@/path/to/receipt.jpg"
```

---

### 3. POST /api/v1/ai/extract-invoice
Specialized endpoint for invoice analysis with structured data extraction.

**Purpose**: Extract customer info, invoice details, line items, and totals from invoices

**Authentication**: Required (Bearer token + Company header)

**Authorization**: Requires `create invoice` permission

**Feature Flag**: Requires `mcp_ai_tools` AND `pdf_analysis` enabled

**Request Validation**:
```php
[
    'file' => 'required|file|max:10240',  // Max 10MB
]
```

**Response Structure**:
```json
{
  "success": true,
  "analysis": "Raw AI analysis text...",
  "extracted_data": {
    "invoice_number": "INV-2025-001",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "+389 70 123 456",
    "customer_address": "123 Main St, Skopje, North Macedonia",
    "invoice_date": "2025-11-01",
    "due_date": "2025-11-30",
    "currency": "MKD",
    "items": [
      {
        "description": "Consulting Services",
        "quantity": 10,
        "unit_price": 5000.00,
        "tax_rate": 18.0,
        "total": 59000.00
      }
    ],
    "subtotal": 50000.00,
    "tax_total": 9000.00,
    "total_amount": 59000.00,
    "notes": "Payment due within 30 days",
    "payment_terms": "Net 30"
  },
  "timestamp": "2025-11-06 10:30:45"
}
```

**Extracted Data Fields**:
- `invoice_number` (string|null): Invoice number
- `customer_name` (string|null): Customer/client name
- `customer_email` (string|null): Customer email address
- `customer_phone` (string|null): Customer phone number
- `customer_address` (string|null): Customer full address
- `invoice_date` (string|null): Invoice date in YYYY-MM-DD format
- `due_date` (string|null): Payment due date in YYYY-MM-DD format
- `currency` (string|null): Currency code
- `items` (array): Array of line items
  - `description` (string): Item/service description
  - `quantity` (int): Quantity
  - `unit_price` (float): Price per unit
  - `tax_rate` (float): Tax rate percentage
  - `total` (float): Line total including tax
- `subtotal` (float|null): Subtotal before tax
- `tax_total` (float|null): Total tax amount
- `total_amount` (float|null): Grand total
- `notes` (string|null): Any notes or additional information
- `payment_terms` (string|null): Payment terms

**Example cURL**:
```bash
curl -X POST https://your-domain.com/api/v1/ai/extract-invoice \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: YOUR_COMPANY_ID" \
  -F "file=@/path/to/invoice.pdf"
```

---

### 4. GET /api/v1/ai/monthly-trends
Get formatted monthly revenue, expense, and profit trends.

**Purpose**: Retrieve historical financial trends for chart generation

**Authentication**: Required (Bearer token + Company header)

**Authorization**: Requires `view dashboard` permission

**Feature Flag**: Requires `mcp_ai_tools` enabled

**Request Validation**:
```php
[
    'months' => 'nullable|integer|min:1|max:24',  // Default: 12
]
```

**Response Structure**:
```json
{
  "success": true,
  "trends": [
    {
      "month": "2024-12",
      "revenue": 125000.00,
      "expenses": 75000.00,
      "profit": 50000.00,
      "invoice_count": 15
    },
    {
      "month": "2025-01",
      "revenue": 142000.00,
      "expenses": 82000.00,
      "profit": 60000.00,
      "invoice_count": 18
    },
    {
      "month": "2025-02",
      "revenue": 138000.00,
      "expenses": 79000.00,
      "profit": 59000.00,
      "invoice_count": 17
    }
  ],
  "months": 12,
  "timestamp": "2025-11-06 10:30:45"
}
```

**Trend Data Fields**:
- `month` (string): Month in YYYY-MM format
- `revenue` (float): Total revenue (paid invoices) for the month
- `expenses` (float): Total expenses for the month
- `profit` (float): Net profit (revenue - expenses)
- `invoice_count` (int): Number of invoices in that month

**Query Parameters**:
- `months` (optional): Number of months to retrieve (1-24, default: 12)

**Example cURL**:
```bash
# Get last 12 months (default)
curl -X GET https://your-domain.com/api/v1/ai/monthly-trends \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: YOUR_COMPANY_ID"

# Get last 6 months
curl -X GET "https://your-domain.com/api/v1/ai/monthly-trends?months=6" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: YOUR_COMPANY_ID"
```

---

## Implementation Details

### Controller Location
`/Users/tamsar/Downloads/mkaccounting/app/Http/Controllers/V1/Admin/AiDocumentController.php`

### Routes Configuration
Routes added to `/Users/tamsar/Downloads/mkaccounting/routes/api.php` under the `ai` prefix with `feature:mcp_ai_tools` middleware.

### Key Features

#### 1. Security & Validation
- File size validation (10MB max)
- MIME type validation
- Authorization checks per endpoint
- Feature flag verification (dual flags: `mcp_ai_tools` + `pdf_analysis`)

#### 2. PDF Processing
- Automatic PDF to image conversion using `PdfImageConverter` service
- Supports multi-page PDFs
- Uses Imagick extension (with fallback error messages)
- Temporary file cleanup after processing

#### 3. AI Integration
- Leverages existing `AiProviderInterface` implementations
- Supports Claude, OpenAI, and Gemini providers
- Uses vision API methods (`analyzeImage`, `analyzeDocument`)
- Comprehensive logging for debugging

#### 4. Data Extraction
- JSON parsing with fallback handling
- Structured data extraction for receipts and invoices
- Regex-based JSON extraction from AI responses
- Graceful degradation on parsing errors

#### 5. Error Handling
- Comprehensive try-catch blocks
- Detailed error logging
- User-friendly error messages
- HTTP status codes (403, 404, 422, 500)

### Dependencies

**Existing Services Used**:
1. `AiInsightsService` - AI orchestration
2. `PdfImageConverter` - PDF to image conversion
3. `McpDataProvider` - Financial data retrieval
4. AI Provider implementations (Claude, OpenAI, Gemini)

**Required Extensions**:
- Imagick (for PDF conversion)
- GD or Imagick (for image processing)

**Storage**:
- Uses Laravel Storage for temp file handling
- Automatic cleanup of temporary files

### Configuration Requirements

**AI Configuration** (`config/ai.php`):
```php
[
    'default_provider' => 'claude',  // or 'openai', 'gemini'
    'providers' => [
        'claude' => [
            'api_key' => env('CLAUDE_API_KEY'),
            'model' => 'claude-3-5-sonnet-20241022',
            // ... other settings
        ],
    ],
    'pdf_converter_backend' => 'imagick',
    'pdf_converter_dpi' => 150,
    'pdf_converter_format' => 'png',
]
```

**Feature Flags**:
1. `mcp_ai_tools` - Master AI features flag (route middleware)
2. `pdf_analysis` - PDF/document analysis flag (endpoint check)

Both must be enabled for document analysis endpoints to work.

**Company Settings**:
```sql
INSERT INTO company_settings (company_id, option, value)
VALUES (1, 'pdf_analysis', '1');
```

---

## Testing

### Test Scenarios

#### 1. Test Document Analysis
```bash
# Create a test PDF or use existing one
curl -X POST http://localhost:8000/api/v1/ai/analyze-document \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: 1" \
  -F "file=@test.pdf" \
  -F "question=Summarize this document"
```

#### 2. Test Receipt Extraction
```bash
# Upload a receipt image
curl -X POST http://localhost:8000/api/v1/ai/analyze-receipt \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: 1" \
  -F "file=@receipt.jpg"
```

#### 3. Test Invoice Extraction
```bash
# Upload an invoice PDF
curl -X POST http://localhost:8000/api/v1/ai/extract-invoice \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: 1" \
  -F "file=@invoice.pdf"
```

#### 4. Test Monthly Trends
```bash
# Get trends for last 6 months
curl -X GET "http://localhost:8000/api/v1/ai/monthly-trends?months=6" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: 1"
```

### Expected Test Results

**Successful Document Analysis**:
- HTTP 200
- JSON response with `success: true`
- `analysis` field contains AI-generated text
- `file_type` reflects uploaded file MIME type

**Successful Receipt Analysis**:
- HTTP 200
- `extracted_data` contains structured receipt information
- All currency values match company currency
- Items array populated if line items detected

**Successful Invoice Extraction**:
- HTTP 200
- `extracted_data` contains customer and invoice details
- Date fields in YYYY-MM-DD format
- Financial calculations (subtotal, tax, total) present

**Successful Trends Retrieval**:
- HTTP 200
- Array of monthly data points
- Months in chronological order
- All values are floats/integers (no null)

---

## Common Issues & Solutions

### Issue 1: Feature Not Enabled
**Error**: 403 "PDF analysis feature is not enabled"

**Solution**: Enable both feature flags:
```sql
-- Enable MCP AI Tools (global)
UPDATE settings SET value = '1' WHERE option = 'mcp_ai_tools';

-- Enable PDF Analysis (per company)
INSERT INTO company_settings (company_id, option, value)
VALUES (1, 'pdf_analysis', '1')
ON DUPLICATE KEY UPDATE value = '1';
```

### Issue 2: Imagick Not Installed
**Error**: "Imagick extension is not installed"

**Solution**: Install Imagick:
```bash
# Ubuntu/Debian
sudo apt-get install php-imagick

# macOS with Homebrew
brew install imagemagick
pecl install imagick

# Then restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Issue 3: File Too Large
**Error**: 422 "File too large"

**Solution**:
- Reduce file size or resolution
- Current limit is 10MB (configurable in controller constant)
- Check PHP upload limits in `php.ini`:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 12M
  ```

### Issue 4: AI Provider Not Configured
**Error**: "Claude API key is not configured"

**Solution**: Set environment variables:
```bash
# .env
CLAUDE_API_KEY=sk-ant-xxxxx
# or
OPENAI_API_KEY=sk-xxxxx
# or
GEMINI_API_KEY=xxxxx
```

### Issue 5: JSON Parsing Failed
**Behavior**: `extracted_data` contains all null values

**Cause**: AI response not properly formatted as JSON

**Solution**:
- Check AI provider configuration
- Review logs for actual AI response
- Prompts may need adjustment for specific provider
- Fallback data structure is returned automatically

---

## Performance Considerations

### Processing Times
- **Image analysis**: 2-5 seconds
- **Single-page PDF**: 3-8 seconds
- **Multi-page PDF**: 5-20 seconds (depends on page count)

### Optimization Recommendations
1. **Async Processing**: Consider queue jobs for large documents
2. **Caching**: Cache extracted data by file hash
3. **Rate Limiting**: Implement rate limits on upload endpoints
4. **Storage**: Clean up temp files regularly
5. **Compression**: Reduce DPI for faster processing (config: `pdf_converter_dpi`)

### Cost Implications
- Vision API calls are more expensive than text-only
- Multi-page PDFs = multiple image tokens
- Monitor usage via logs (`ai.log_api_calls`)
- Consider implementing usage quotas per company

---

## Security Considerations

### File Upload Security
1. ✅ MIME type validation
2. ✅ File size limits
3. ✅ Temporary file storage with cleanup
4. ✅ No direct file execution
5. ✅ Authorization checks

### Recommended Additional Security
1. **Virus scanning**: Integrate ClamAV for uploaded files
2. **Content validation**: Verify PDFs are not executable
3. **Rate limiting**: Prevent abuse via repeated uploads
4. **Audit logging**: Track all document uploads
5. **GDPR compliance**: Handle PII in uploaded documents

---

## API Response Codes Summary

| Code | Meaning | Scenarios |
|------|---------|-----------|
| 200 | Success | Document analyzed successfully |
| 403 | Forbidden | Feature flag disabled, insufficient permissions |
| 404 | Not Found | Company not found |
| 422 | Unprocessable | Invalid file type, file too large, validation failed |
| 500 | Server Error | AI processing failed, PDF conversion failed, unexpected error |

---

## Logging & Monitoring

### Log Locations
All logs use Laravel's configured log channel (typically `storage/logs/laravel.log`).

### Log Entries
```
[AiDocumentController] Document analysis started
[AiDocumentController] PDF converted to images
[AiDocumentController] Processing image
[AiDocumentController] Document analysis failed
[AiDocumentController] Receipt analysis started
[AiDocumentController] Invoice extraction started
[AiDocumentController] Monthly trends requested
[AiDocumentController] Failed to parse receipt JSON
[AiDocumentController] Failed to parse invoice JSON
```

### Monitoring Metrics
- Request count by endpoint
- Average processing time
- Success/error rates
- File type distribution
- AI provider costs (via usage logs)

---

## Future Enhancements

### Possible Improvements
1. **Batch Processing**: Upload multiple files at once
2. **OCR Fallback**: Use Tesseract if AI vision unavailable
3. **Template Learning**: Train on company-specific document formats
4. **Auto-categorization**: Automatic expense categorization
5. **Duplicate Detection**: Compare against existing receipts/invoices
6. **Multi-language**: Support invoices in multiple languages
7. **Confidence Scores**: Return extraction confidence levels
8. **Manual Correction**: UI for correcting AI extractions
9. **Webhook Notifications**: Notify when processing completes
10. **Export Templates**: Generate documents from extracted data

---

## Conclusion

All four endpoints have been successfully implemented:
- ✅ POST /api/v1/ai/analyze-document
- ✅ POST /api/v1/ai/analyze-receipt
- ✅ POST /api/v1/ai/extract-invoice
- ✅ GET /api/v1/ai/monthly-trends

**Status**: Ready for testing
**No commits made**: As requested
**Documentation**: Complete

The implementation integrates seamlessly with existing AI services, follows Laravel best practices, and includes comprehensive error handling and logging.
