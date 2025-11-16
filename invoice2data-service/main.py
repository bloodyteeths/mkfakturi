import io
import os
import re
from typing import Any, Dict, List, Optional

from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.responses import JSONResponse

try:
    from invoice2data import extract_data, read_templates  # type: ignore
except ImportError:  # pragma: no cover - runtime env issue, not logic
    extract_data = None  # type: ignore
    read_templates = None  # type: ignore

try:
    import pytesseract  # type: ignore
    from PIL import Image  # type: ignore
    import numpy as np  # type: ignore
except Exception:  # pragma: no cover - optional OCR pipeline
    pytesseract = None  # type: ignore
    Image = None  # type: ignore
    np = None  # type: ignore

app = FastAPI(title="Invoice2Data Microservice", version="1.0.0")


def load_templates():
    """
    Load invoice2data templates from the local templates directory.
    """
    templates_dir = os.path.join(os.path.dirname(__file__), "templates")
    if read_templates is None or not os.path.isdir(templates_dir):
        return []
    return read_templates(templates_dir)


TEMPLATES = load_templates()


def normalize_invoice_data(raw: Optional[Dict[str, Any]]) -> Dict[str, Any]:
    """
    Normalize invoice2data output into a unified schema.
    """
    if not raw:
        raise ValueError("No data returned from invoice2data")

    supplier = {
        "name": raw.get("issuer") or raw.get("supplier") or raw.get("company"),
        "tax_id": raw.get("vat_id") or raw.get("tax_id"),
        "address": raw.get("address"),
        "email": raw.get("email"),
    }

    invoice = {
        "number": raw.get("invoice_number") or raw.get("number"),
        "date": raw.get("date"),
        "due_date": raw.get("due_date"),
        "currency": raw.get("currency") or raw.get("currency_code"),
    }

    totals = {
        "total": raw.get("amount") or raw.get("total"),
        "subtotal": raw.get("net") or raw.get("subtotal"),
        "tax": raw.get("tax"),
    }

    line_items: List[Dict[str, Any]] = []
    lines = raw.get("lines") or raw.get("items") or []
    for line in lines:
        line_items.append(
            {
                "description": line.get("desc") or line.get("description") or line.get("product"),
                "name": line.get("product") or line.get("name") or line.get("desc"),
                "quantity": line.get("qty") or line.get("quantity") or 1,
                "unit_price": line.get("price") or line.get("unit_price"),
                "tax": line.get("tax"),
                "total": line.get("total") or line.get("amount"),
                "discount": line.get("discount"),
            }
        )

    taxes: List[Dict[str, Any]] = []
    tax_lines = raw.get("taxes") or []
    for tax in tax_lines:
        taxes.append(
            {
                "name": tax.get("name"),
                "rate": tax.get("rate"),
                "amount": tax.get("amount"),
            }
        )

    return {
        "supplier": supplier,
        "invoice": invoice,
        "line_items": line_items,
        "taxes": taxes,
        "totals": totals,
        "raw": raw,
    }


def _extract_text_from_image(contents: bytes) -> str:
    """
    Run OCR on an image using Tesseract (via pytesseract).
    """
    import logging
    logger = logging.getLogger(__name__)

    if pytesseract is None or Image is None:
        raise RuntimeError("OCR is not available (pytesseract/Pillow not installed)")

    image = Image.open(io.BytesIO(contents)).convert("RGB")

    # Image preprocessing for better OCR accuracy
    if np is not None:
        import cv2
        img_array = np.array(image)

        # Convert to grayscale
        gray = cv2.cvtColor(img_array, cv2.COLOR_RGB2GRAY)

        # Apply adaptive thresholding to improve contrast
        binary = cv2.adaptiveThreshold(
            gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2
        )

        # Denoise
        denoised = cv2.fastNlMeansDenoising(binary, h=10)

        # Convert back to PIL Image
        image = Image.fromarray(denoised)

        logger.info(f"OCR preprocessing applied - image size: {image.size}")

    langs = os.getenv("OCR_LANGS", "eng")
    logger.info(f"Running Tesseract OCR with languages: {langs}")

    # Configure Tesseract for better Cyrillic text recognition
    custom_config = r'--oem 3 --psm 6'  # OEM 3 = Default, PSM 6 = Assume uniform block of text

    text = pytesseract.image_to_string(image, lang=langs, config=custom_config)

    # Log extracted text for debugging
    logger.info(f"OCR extracted text ({len(text)} chars):\n{text[:500]}..." if len(text) > 500 else f"OCR extracted text:\n{text}")

    # Strip whitespace but don't return empty string if only whitespace detected
    text = text.strip()

    return text or ""


def _parse_text_to_raw(text: str) -> Dict[str, Any]:
    """
    Very lightweight fallback parser for OCR text.

    This does NOT try to be perfect; it extracts a few key fields so that
    normalize_invoice_data() can build a consistent structure.
    """
    import logging
    logger = logging.getLogger(__name__)

    lines = [ln.strip() for ln in text.splitlines() if ln.strip()]
    logger.info(f"Parser received {len(lines)} non-empty lines")
    logger.info(f"First 10 lines: {lines[:10]}")

    supplier_name = lines[0] if lines else None
    logger.info(f"Extracted supplier name: {supplier_name}")

    # Simple date detection (YYYY-MM-DD or DD.MM.YYYY)
    date_match = re.search(r"(\d{4}-\d{2}-\d{2})", text)
    if not date_match:
        date_match = re.search(r"(\d{2}\.\d{2}\.\d{4})", text)

    invoice_date = date_match.group(1) if date_match else None

    # Find candidate total amounts (numbers with 2 decimals or whole)
    # Prefer numbers that appear on lines containing "total" / "Вкупно" etc.
    # and clamp values to a reasonable range so we do not accidentally
    # interpret fiscal IDs or long codes as monetary totals.
    amount_pattern = re.compile(r"(\d+[.,]\d{2}|\d+)")
    all_numbers: List[float] = []
    keyword_numbers: List[float] = []

    MAX_REASONABLE_TOTAL = 1_000_000.0  # 1M in invoice currency units

    def _parse_number(raw: str) -> Optional[float]:
        value = raw.replace(",", ".")
        try:
            num = float(value)
        except ValueError:
            return None
        if num <= 0 or num > MAX_REASONABLE_TOTAL:
            return None
        return num

    keywords = [
        "total",
        "вкупно",
        "vkupno",
        "vkupen iznos",
        "вкупен износ",
        "износ за плаќање",
        "iznos za plakjanje",
        "iznos za plakjanje",
    ]

    # Scan line by line so we can detect numbers near likely "total" labels
    for line in lines:
        lower = line.lower()
        line_numbers: List[float] = []
        for m in amount_pattern.findall(line):
            num = _parse_number(m)
            if num is not None:
                all_numbers.append(num)
                line_numbers.append(num)

        if any(kw in lower for kw in keywords) and line_numbers:
            # For total lines, prefer the last number on the line
            keyword_numbers.append(line_numbers[-1])

    # Prefer numbers from keyword lines; otherwise fall back to the largest
    # reasonable number we saw anywhere in the text.
    if keyword_numbers:
        total_amount: Optional[float] = max(keyword_numbers)
    elif all_numbers:
        total_amount = max(all_numbers)
    else:
        total_amount = None

    raw: Dict[str, Any] = {
        "issuer": supplier_name,
        "invoice_number": None,
        "date": invoice_date,
        "amount": int(total_amount * 100) if total_amount is not None else None,
        "total": int(total_amount * 100) if total_amount is not None else None,
        "tax": None,
        "lines": [],
    }

    logger.info(f"Parser extracted data: supplier='{supplier_name}', date={invoice_date}, total_amount={total_amount}")

    return raw


@app.get("/health")
def health() -> Dict[str, Any]:
    """Health check endpoint with diagnostic info."""
    import os
    import glob

    # Check for ZXing JAR in common locations
    jar_locations = [
        "/root/.local/pyzxing",
        os.path.expanduser("~/.local/pyzxing"),
        os.path.join(os.getcwd(), ".local/pyzxing"),
    ]

    jar_status = {}
    for location in jar_locations:
        if os.path.exists(location):
            jars = glob.glob(os.path.join(location, "*.jar"))
            jar_status[location] = {
                "exists": True,
                "jars": [os.path.basename(j) for j in jars],
                "files": os.listdir(location) if os.path.isdir(location) else []
            }
        else:
            jar_status[location] = {"exists": False}

    return {
        "status": "ok",
        "pyzxing_available": BarCodeReader is not None,
        "jar_locations": jar_status,
        "home": os.path.expanduser("~"),
        "user": os.environ.get("USER", "unknown"),
        "cwd": os.getcwd()
    }


@app.post("/scan-datamatrix")
async def scan_datamatrix(file: UploadFile = File(...)) -> JSONResponse:
    """
    Scan DataMatrix barcode from an uploaded image (Macedonian fiscal receipts).
    Uses ZXing Java library via pyzxing for better DataMatrix detection.
    """
    import logging
    import tempfile
    import os
    import glob as glob_module
    logger = logging.getLogger(__name__)

    if BarCodeReader is None:
        raise HTTPException(
            status_code=501,
            detail="DataMatrix scanning not available (pyzxing/Java not installed)",
        )

    try:
        contents = await file.read()
        if not contents:
            raise HTTPException(status_code=400, detail="Empty file")

        # pyzxing requires a file path, so write to temp file
        with tempfile.NamedTemporaryFile(delete=False, suffix=".jpg") as tmp:
            tmp.write(contents)
            tmp_path = tmp.name

        logger.info(f"Scanning DataMatrix from image: {tmp_path}")

        # Check if JAR exists before initializing
        jar_path = os.path.expanduser("~/.local/pyzxing")
        jars = glob_module.glob(os.path.join(jar_path, "javase-*-jar-with-dependencies.jar"))
        logger.info(f"JAR check before BarCodeReader init: path={jar_path}, jars_found={jars}")

        # Initialize ZXing reader
        logger.info("Initializing BarCodeReader...")
        try:
            reader = BarCodeReader()
            logger.info(f"BarCodeReader initialized successfully, lib_path={reader.lib_path}")
        except Exception as e:
            logger.error(f"BarCodeReader initialization failed: {e}", exc_info=True)
            raise

        # Scan for DataMatrix codes
        logger.info("Calling reader.decode...")
        results = reader.decode(tmp_path)
        logger.info(f"Decode completed")

        logger.info(f"ZXing scan results: {results}")

        # Clean up temp file
        import os
        os.unlink(tmp_path)

        if not results or len(results) == 0:
            raise HTTPException(status_code=422, detail="No barcodes detected in image")

        # Filter for DataMatrix codes only (ignore UPC, EAN, etc.)
        datamatrix_results = []
        for result in results if isinstance(results, list) else [results]:
            barcode_format = result.get('format', b'')
            # Handle both bytes and string format
            if isinstance(barcode_format, bytes):
                barcode_format = barcode_format.decode('utf-8', errors='ignore')

            logger.info(f"Found barcode - Format: {barcode_format}, Keys: {result.keys()}")

            if barcode_format == 'DATA_MATRIX':
                datamatrix_results.append(result)

        if not datamatrix_results:
            raise HTTPException(
                status_code=422,
                detail=f"No DataMatrix code found. Found {len(results)} other barcode(s): {[r.get('format') for r in (results if isinstance(results, list) else [results])]}"
            )

        # Use first DataMatrix result
        first_result = datamatrix_results[0]

        logger.info(f"DataMatrix result: {first_result}")

        # pyzxing returns bytes, need to decode to string
        raw_data = first_result.get("raw") or first_result.get("parsed", b"")
        if isinstance(raw_data, bytes):
            datamatrix_text = raw_data.decode('utf-8', errors='ignore')
        else:
            datamatrix_text = str(raw_data) if raw_data else ""

        if not datamatrix_text:
            logger.error(f"DataMatrix found but data is empty. Full result: {first_result}")
            raise HTTPException(status_code=422, detail="DataMatrix detected but could not decode")

        logger.info(f"DataMatrix decoded: {datamatrix_text[:100]}...")

        return JSONResponse(content={
            "success": True,
            "format": first_result.get("format", "DATA_MATRIX"),
            "data": datamatrix_text,
            "length": len(datamatrix_text)
        })

    except HTTPException:
        raise
    except Exception as exc:
        import logging
        logging.exception("DataMatrix scanning failed")
        raise HTTPException(status_code=500, detail=f"Scan error: {str(exc)}")


@app.post("/ocr")
async def extract_text(file: UploadFile = File(...)) -> JSONResponse:
    """
    Extract raw text from an image using OCR.
    Returns only the text, no parsing.
    """
    try:
        contents = await file.read()
        if not contents:
            raise HTTPException(status_code=400, detail="Empty file")

        if pytesseract is None or Image is None:
            raise HTTPException(
                status_code=500,
                detail="OCR not available on service (pytesseract/Pillow missing)",
            )

        text = _extract_text_from_image(contents)

        return JSONResponse(content={
            "success": True,
            "text": text,
            "length": len(text)
        })

    except HTTPException:
        raise
    except Exception as exc:
        import logging
        logging.exception("OCR extraction failed")
        raise HTTPException(status_code=500, detail=f"OCR error: {str(exc)}")


@app.post("/parse")
async def parse_invoice(file: UploadFile = File(...)) -> JSONResponse:
    """
    Parse an uploaded invoice:
    - PDFs: use invoice2data when available.
    - Images: use OCR + lightweight text parser (does not require invoice2data).
    """
    try:
        contents = await file.read()
        if not contents:
            raise HTTPException(status_code=400, detail="Empty file")

        content_type = (file.content_type or "").lower()
        filename = (file.filename or "").lower()

        is_pdf = content_type == "application/pdf" or filename.endswith(".pdf")
        is_image = content_type.startswith("image/") or filename.endswith((".jpg", ".jpeg", ".png"))

        if is_pdf:
            if extract_data is None:
                # Invoice2data is not available on this deployment; signal that
                # PDF template parsing is not supported here.
                raise HTTPException(
                    status_code=501,
                    detail="PDF template parsing (invoice2data) is not available on this deployment",
                )

            # invoice2data expects a file-like object or path; we use BytesIO.
            buffer = io.BytesIO(contents)
            data = extract_data(buffer, templates=TEMPLATES if TEMPLATES else None)

            if not data:
                raise HTTPException(status_code=422, detail="No template matched invoice")

            normalized = normalize_invoice_data(data)
            return JSONResponse(content=normalized)

        if is_image:
            if pytesseract is None or Image is None:
                raise HTTPException(
                    status_code=500,
                    detail="OCR not available on parser service (pytesseract/Pillow missing)",
                )

            text = _extract_text_from_image(contents)
            if not text.strip():
                raise HTTPException(status_code=422, detail="No text detected in image")

            raw = _parse_text_to_raw(text)
            normalized = normalize_invoice_data(raw)
            return JSONResponse(content=normalized)

        raise HTTPException(status_code=400, detail="Unsupported file type")
    except HTTPException:
        raise
    except Exception as exc:  # pragma: no cover - defensive
        raise HTTPException(status_code=400, detail=str(exc))
