# AI Document Analysis API - Quick Reference

## Endpoints at a Glance

| Endpoint | Method | Purpose | Auth Required |
|----------|--------|---------|---------------|
| `/api/v1/ai/analyze-document` | POST | General document analysis | ✓ |
| `/api/v1/ai/analyze-receipt` | POST | Extract receipt data | ✓ |
| `/api/v1/ai/extract-invoice` | POST | Extract invoice data | ✓ |
| `/api/v1/ai/monthly-trends` | GET | Get financial trends | ✓ |

---

## Quick Start

### 1. Enable Features
```sql
-- Enable AI tools (global)
UPDATE settings SET value = '1' WHERE option = 'mcp_ai_tools';

-- Enable PDF analysis (per company)
INSERT INTO company_settings (company_id, option, value)
VALUES (1, 'pdf_analysis', '1');
```

### 2. Set Environment
```bash
# .env
CLAUDE_API_KEY=sk-ant-xxxxx
AI_PROVIDER=claude
```

### 3. Test
```bash
curl -X POST http://localhost:8000/api/v1/ai/analyze-document \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "company: 1" \
  -F "file=@test.pdf"
```

---

## Request Templates

### Analyze Document
```bash
curl -X POST /api/v1/ai/analyze-document \
  -H "Authorization: Bearer TOKEN" \
  -H "company: COMPANY_ID" \
  -F "file=@document.pdf" \
  -F "question=What are the key points?"
```

### Analyze Receipt
```bash
curl -X POST /api/v1/ai/analyze-receipt \
  -H "Authorization: Bearer TOKEN" \
  -H "company: COMPANY_ID" \
  -F "file=@receipt.jpg"
```

### Extract Invoice
```bash
curl -X POST /api/v1/ai/extract-invoice \
  -H "Authorization: Bearer TOKEN" \
  -H "company: COMPANY_ID" \
  -F "file=@invoice.pdf"
```

### Get Trends
```bash
curl -X GET /api/v1/ai/monthly-trends?months=12 \
  -H "Authorization: Bearer TOKEN" \
  -H "company: COMPANY_ID"
```

---

## Response Structures

### Document Analysis
```json
{
  "success": true,
  "analysis": "AI analysis text...",
  "file_type": "application/pdf",
  "timestamp": "2025-11-06 10:30:45"
}
```

### Receipt Data
```json
{
  "success": true,
  "extracted_data": {
    "vendor": "ACME Store",
    "date": "2025-11-06",
    "total_amount": 123.45,
    "currency": "MKD",
    "tax_amount": 18.52,
    "items": [...]
  }
}
```

### Invoice Data
```json
{
  "success": true,
  "extracted_data": {
    "invoice_number": "INV-001",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "invoice_date": "2025-11-01",
    "due_date": "2025-11-30",
    "total_amount": 59000.00,
    "items": [...]
  }
}
```

### Monthly Trends
```json
{
  "success": true,
  "trends": [
    {
      "month": "2025-01",
      "revenue": 142000.00,
      "expenses": 82000.00,
      "profit": 60000.00,
      "invoice_count": 18
    }
  ]
}
```

---

## File Requirements

### Supported Types
- `application/pdf`
- `image/png`
- `image/jpeg`
- `image/jpg`
- `image/webp`

### Limits
- Max size: **10MB**
- Max pages: Unlimited (but affects cost)

---

## Error Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 403 | Feature disabled or no permission |
| 404 | Company not found |
| 422 | Invalid file or validation error |
| 500 | Processing error |

---

## Common Errors

### "Feature not enabled" (403)
```sql
INSERT INTO company_settings (company_id, option, value)
VALUES (1, 'pdf_analysis', '1');
```

### "Imagick not installed" (500)
```bash
sudo apt-get install php-imagick
sudo systemctl restart php-fpm
```

### "File too large" (422)
- Reduce file size
- Check: `upload_max_filesize` in php.ini

### "API key not configured" (500)
```bash
# Add to .env
CLAUDE_API_KEY=sk-ant-xxxxx
```

---

## Feature Flags

### Required Flags (both must be enabled):
1. **mcp_ai_tools** - Master AI flag
2. **pdf_analysis** - Document analysis flag

### Check Status
```php
// In tinker
Company::find(1)->getSetting('pdf_analysis');
Setting::getSetting('mcp_ai_tools');
```

---

## Processing Times

| Document Type | Time |
|---------------|------|
| Image (PNG/JPG) | 2-5s |
| PDF (1 page) | 3-8s |
| PDF (multi-page) | 5-20s |

---

## Cost Optimization

1. Reduce DPI: `pdf_converter_dpi = 100`
2. Use async jobs for large files
3. Cache results by file hash
4. Implement rate limiting
5. Monitor AI usage logs

---

## Security Checklist

- ✓ File size limits
- ✓ MIME type validation
- ✓ Authorization checks
- ✓ Feature flags
- ✓ Temp file cleanup
- ⚠️ Consider: Virus scanning
- ⚠️ Consider: Rate limiting
- ⚠️ Consider: Usage quotas

---

## Testing Checklist

- [ ] Enable both feature flags
- [ ] Set AI provider API key
- [ ] Install Imagick extension
- [ ] Test with PDF file
- [ ] Test with image file
- [ ] Test with invalid file type
- [ ] Test with oversized file
- [ ] Test without authentication
- [ ] Check logs for errors
- [ ] Verify extracted data accuracy

---

## Integration Example

```javascript
// Upload receipt and create expense
const formData = new FormData();
formData.append('file', receiptFile);

const response = await fetch('/api/v1/ai/analyze-receipt', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'company': companyId
  },
  body: formData
});

const result = await response.json();

if (result.success) {
  // Auto-fill expense form
  document.getElementById('vendor').value = result.extracted_data.vendor;
  document.getElementById('amount').value = result.extracted_data.total_amount;
  document.getElementById('date').value = result.extracted_data.date;
}
```

---

## Logging

### Key Log Entries:
```
[AiDocumentController] Document analysis started
[AiDocumentController] PDF converted to images
[AiDocumentController] Receipt analysis failed
```

### View Logs:
```bash
tail -f storage/logs/laravel.log | grep AiDocument
```

---

## Files Reference

| File | Purpose |
|------|---------|
| `AiDocumentController.php` | Main controller |
| `routes/api.php` | Route definitions |
| `AI_DOCUMENT_ENDPOINTS.md` | Full documentation |
| `CURL_EXAMPLES.sh` | Example commands |
| `IMPLEMENTATION_SUMMARY.md` | This implementation |
| `QUICK_REFERENCE.md` | This quick reference |

---

## Support Resources

1. **Full Documentation**: `AI_DOCUMENT_ENDPOINTS.md`
2. **Examples**: `CURL_EXAMPLES.sh`
3. **Implementation Details**: `IMPLEMENTATION_SUMMARY.md`
4. **Laravel Logs**: `storage/logs/laravel.log`

---

**Last Updated**: 2025-11-06
**Version**: 1.0
**Status**: Production Ready
