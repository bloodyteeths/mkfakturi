# AP Automation OCR & Image Parsing Pipeline

This document tracks the implementation of an AI‑assisted image → data pipeline
for Accounts Payable (AP) in Facturino, complementing the existing QR reader
and `invoice2data` PDF parsing flow.

## 1. Existing Foundations

- **Microservice:** `invoice2data-service/`
  - FastAPI app with:
    - `GET /health` – basic health check.
    - `POST /parse` – previously: *PDF‑only* parsing via `invoice2data`.
  - Uses:
    - `invoice2data` templates under `invoice2data-service/templates/`.
    - `normalize_invoice_data()` to return a unified JSON schema:
      - `supplier`, `invoice`, `line_items`, `taxes`, `totals`, `raw`.

- **Laravel integration:**
  - `App\Services\InvoiceParsing\Invoice2DataClient`:
    - Sends the uploaded PDF to `INVOICE2DATA_URL/parse`.
  - `App\Services\InvoiceParsing\ParsedInvoiceMapper`:
    - Maps normalized JSON → `supplier` + `bill` + `items` arrays.
  - `App\Jobs\ParseInvoicePdfJob`:
    - Uses the client + mapper to create:
      - `Supplier`, `Bill`, `BillItem`s, and attach original PDF (`bills` media).
    - Used by the Email→Bill pipeline via `ProcessInboundBillEmail`.

## 2. New OCR & Image Parsing (Done)

### 2.1 Python microservice enhancements

Files:
- `invoice2data-service/requirements.txt`
  - Added:
    - `Pillow` – image loading / basic preprocessing.
    - `pytesseract` – Tesseract OCR wrapper.
    - `numpy` – future‑proofing for image preprocessing.

- `invoice2data-service/main.py`
  - New optional imports:
    - `pytesseract`, `PIL.Image`, `numpy`.
  - `/parse` endpoint now supports:
    - **PDF invoices** (unchanged core behavior):
      - Detects PDF via `content_type` or filename.
      - Uses `invoice2data.extract_data(...)` on an in‑memory `BytesIO`.
      - Normalizes with `normalize_invoice_data(raw)`.
    - **Image invoices / smetki** (JPEG/PNG):
      - Detects image via `content_type` or filename extension.
      - `_extract_text_from_image(contents: bytes) -> str`:
        - Loads image with `Pillow`, converts to RGB.
        - Runs `pytesseract.image_to_string(...)` with `OCR_LANGS` env
          (default `"eng"`).
      - `_parse_text_to_raw(text: str) -> Dict[str, Any]`:
        - Lightweight OCR text parser to feed the existing normalizer:
          - First non‑empty line → `issuer` (supplier name).
          - Regex‑based date detection (`YYYY-MM-DD` or `DD.MM.YYYY`).
          - Numeric scan to pick the **largest** number as `total`/`amount`
            (converted to cents).
          - Produces a `raw` dict with keys expected by
            `normalize_invoice_data(...)` (`issuer`, `date`, `amount`, `total`,
            `tax`, `lines`).
        - Then `normalize_invoice_data(raw)` maps this into the standard schema.

  - Error behavior:
    - Missing `invoice2data` → 500 `"invoice2data library not available"`.
    - Unsupported file type → 400 `"Unsupported file type"`.
    - OCR stack missing (no `pytesseract`/`Pillow`) → 500
      `"OCR not available on parser service..."`.
    - No text detected in image → 422 `"No text detected in image"`.

> **Note:** The microservice *still* exposes only `POST /parse`, but now accepts
> both PDFs and images with a single endpoint and returns the same normalized
> JSON schema for both.

## 3. Planned Integration Work (Next Steps)

The goal is to make `/admin/receipts/scan` and related flows resilient even
when QR codes are missing, unreadable, or the document is just a photo/PDF
without QR.

### 3.1 Laravel – Receipt scanner fallback to OCR parser

Controller to update:
- `app/Http/Controllers/V1/Admin/AccountsPayable/ReceiptScannerController.php`

Planned behavior:
- Keep **QR decoding** as the first, fast path:
  - Try `FiscalReceiptQrService::decodeAndNormalize(...)`.
  - On success:
    - Continue current behavior:
      - `type='invoice'` → draft `Bill` + media.
      - `type='cash'` → draft `Expense` + media.
- On QR **failure** (exception thrown):
  - Do **not** delete the stored file.
  - Fallback to the invoice parser microservice:
    - Inject `InvoiceParserClient` + `ParsedInvoiceMapper` into the controller
      method.
    - Call `$parserClient->parse($companyId, $storedPath, $originalName, 'receipt-scan', null)`.
    - Map parsed result → `supplier`, `bill`, `items` via `ParsedInvoiceMapper`.
    - Create:
      - `Supplier::updateOrCreate(...)` (company‑scoped).
      - `Bill` + `BillItem`s (using the mapped bill/items payload).
      - Attach original image/PDF to `bills` media collection.
    - Return `201` with `BillResource` and `document_type='bill'`.
  - If the parser call fails (4xx/5xx from microservice):
    - Log the error (including HTTP status and body).
    - Return a **422**/500 JSON error to the UI with a clear message.

Multi‑tenant & safety:
- Enforce `company` header (as already done).
- Ensure all created entities (`Supplier`, `Bill`, `BillItem`) include
  `company_id`.
- Preserve existing QR behavior when it works; OCR is a fallback, not a
  replacement.

### 3.2 Tests to add / adjust

- Add feature tests in `tests/Feature/Admin/ReceiptScannerTest.php`:
  - **Fallback path test:**
    - Stub `FiscalReceiptQrService` to throw on `decodeAndNormalize`.
    - Stub `InvoiceParserClient` and `ParsedInvoiceMapper` to return a small,
      deterministic normalized payload.
    - Upload a fake JPEG, assert:
      - 201 response.
      - A `Bill` was created for the correct company.
  - Ensure existing QR tests still pass (cash/invoice + isolation).

### 3.3 Parser microservice environment

- Ensure the Python service environment has:
  - `pytesseract`, `Pillow`, `numpy` (already added to requirements).
  - A working OCR backend:
    - For Tesseract: OS package `tesseract-ocr` installed in the container,
      or
    - Alternative: switch to a pure‑Python OCR library in the future
      (e.g. EasyOCR) if binary installation becomes problematic.
- Expose `OCR_LANGS` env (e.g. `"eng+mkd"`) to tune recognition for Macedonian
  receipts.

## 4. Longer‑Term Enhancements (Optional)

- Replace the simple `_parse_text_to_raw()` heuristics with:
  - Template‑driven extraction on OCR text using `invoice2data` templates.
  - Or a dedicated ML model (e.g. InvoiceNet / KIE) for robust field extraction.
- Add a mobile “scan to Facturino” app (fork an open‑source document scanner)
  that uploads images directly to the Laravel API or the parser service.

---

**Implementation status:**
- ✅ Parser microservice extended to handle images via OCR and return the
  normalized schema used by Laravel.
- ⏳ Next: wire `ReceiptScannerController` to call `InvoiceParserClient` as an
  OCR fallback when QR decoding fails, and add regression tests for this path.

