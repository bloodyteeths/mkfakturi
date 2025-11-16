# Invoice2Data OCR Service

FastAPI microservice for parsing invoices and fiscal receipts using OCR and text extraction.

## Features

- **PDF Parsing**: Template-based extraction using invoice2data
- **Image OCR**: Tesseract OCR with multi-language support
- **Macedonian Fiscal Receipts**: Optimized for Cyrillic text extraction
- **Text Parsing**: Lightweight fallback parser for OCR output

## Language Support

The service includes Tesseract language packs for:

- **mkd** (Macedonian): Primary language for fiscal receipts
- **eng** (English): For mixed-language documents
- **srp** (Serbian): Additional Cyrillic support

Configure via the `OCR_LANGS` environment variable (default: `mkd+eng+srp`).

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `OCR_LANGS` | `mkd+eng+srp` | Tesseract language codes (+ separated) |
| `PYTHONDONTWRITEBYTECODE` | `1` | Disable .pyc files |
| `PYTHONUNBUFFERED` | `1` | Force unbuffered output |

## API Endpoints

### POST /parse

Upload an invoice/receipt for parsing.

**Request**:
- Content-Type: `multipart/form-data`
- Field: `file` (PDF, JPEG, PNG)

**Response** (200 OK):
```json
{
  "supplier": {
    "name": "Company Name",
    "tax_id": "MK12345678901",
    "address": "Street Address",
    "email": "contact@company.mk"
  },
  "invoice": {
    "number": "INV-001",
    "date": "2025-11-16",
    "due_date": "2025-12-16",
    "currency": "MKD"
  },
  "totals": {
    "total": 150000,
    "subtotal": 120000,
    "tax": 30000
  },
  "line_items": [...],
  "taxes": [...]
}
```

**Errors**:
- `400`: Invalid file or unsupported type
- `422`: No text detected (OCR) or no template match (PDF)
- `501`: PDF parsing not available (invoice2data not installed)

### GET /health

Health check endpoint.

**Response**: `{"status": "ok"}`

## Docker Build

```bash
docker build -t invoice2data-service .
docker run -p 8000:8000 invoice2data-service
```

### Custom Language Configuration

```bash
docker run -p 8000:8000 \
  -e OCR_LANGS=mkd+eng \
  invoice2data-service
```

## Development

```bash
pip install -r requirements.txt
uvicorn main:app --reload
```

**Note**: You need Tesseract OCR installed locally:
```bash
# macOS
brew install tesseract tesseract-lang

# Debian/Ubuntu
apt-get install tesseract-ocr tesseract-ocr-mkd tesseract-ocr-eng tesseract-ocr-srp
```

## Testing

```bash
pytest tests/
```

## Deployment (Railway)

The service automatically deploys to Railway on push to main branch.

Required environment variables:
- `OCR_LANGS=mkd+eng+srp` (already set in Dockerfile)

The Dockerfile includes all necessary Tesseract language packs.

## Macedonian Fiscal Receipt Parsing

The service is optimized for Macedonian fiscal receipts with Cyrillic text:

1. **OCR Extraction**: Tesseract with `mkd+eng+srp` languages
2. **Text Parsing**: Lightweight parser recognizing Macedonian keywords
   - "вкупно", "vkupno" (total)
   - "износ за плаќање" (amount to pay)
   - "вкупен износ" (total amount)
3. **Data Normalization**: Standardized output format
4. **Amount Detection**: Smart number extraction with keyword matching

## Architecture

```
Upload Image/PDF
      ↓
Is PDF? → invoice2data (template matching)
      ↓
Is Image? → Tesseract OCR (mkd+eng+srp)
      ↓
Extract Text → Lightweight Parser
      ↓
Normalize Data → JSON Response
```

## Performance

- **OCR Processing**: 3-6 seconds average
- **Memory Usage**: ~200-300MB per request
- **Concurrent Requests**: Handled by uvicorn workers

## Troubleshooting

### "No text detected in image"

**Causes**:
- Image quality too low
- Wrong language configuration
- Tesseract language pack not installed

**Solutions**:
1. Check `OCR_LANGS` includes `mkd` for Macedonian
2. Verify Tesseract language packs installed:
   ```bash
   tesseract --list-langs
   ```
3. Improve image quality (min 300 DPI recommended)

### "OCR not available"

**Cause**: pytesseract or Pillow not installed

**Solution**: Rebuild Docker image or install dependencies:
```bash
pip install pytesseract pillow
```

## License

AGPL-3.0 (inherited from InvoiceShelf parent project)

## References

- [Tesseract OCR](https://github.com/tesseract-ocr/tesseract)
- [invoice2data](https://github.com/invoice-x/invoice2data)
- [FastAPI](https://fastapi.tiangolo.com/)
