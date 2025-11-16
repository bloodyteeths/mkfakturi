"""
invoice2data microservice with smart OCR preprocessing
Version: 1.1.0 - Adaptive preprocessing for high-quality images
"""
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
    Uses smart preprocessing - only applies heavy processing to low-quality images.
    """
    import logging
    logger = logging.getLogger(__name__)

    if pytesseract is None or Image is None:
        raise RuntimeError("OCR is not available (pytesseract/Pillow not installed)")

    image = Image.open(io.BytesIO(contents)).convert("RGB")
    original_image = image.copy()

    langs = os.getenv("OCR_LANGS", "eng")
    logger.info(f"Running Tesseract OCR with languages: {langs}")

    # Try multiple PSM modes for better accuracy
    # PSM 3 = Fully automatic page segmentation (best for receipts)
    # PSM 6 = Assume uniform block of text
    # PSM 4 = Assume single column of text
    psm_modes = [3, 6, 4]

    best_text = ""
    best_length = 0

    for psm in psm_modes:
        custom_config = f'--oem 3 --psm {psm}'

        # First attempt: Try with original image (best for high-quality images)
        logger.info(f"Attempting OCR with original image (PSM {psm})")
        text = pytesseract.image_to_string(original_image, lang=langs, config=custom_config)

        if len(text.strip()) > best_length:
            best_text = text
            best_length = len(text.strip())
            logger.info(f"PSM {psm} (original): extracted {len(text.strip())} chars")

        # Second attempt: Try with preprocessing only if numpy/cv2 available
        # Only apply preprocessing if the original attempt yielded poor results
        if np is not None and len(text.strip()) < 100:
            import cv2
            img_array = np.array(original_image)
            gray = cv2.cvtColor(img_array, cv2.COLOR_RGB2GRAY)

            # Check image quality using variance
            variance = cv2.Laplacian(gray, cv2.CV_64F).var()
            logger.info(f"Image variance (sharpness): {variance:.2f}")

            # Only apply aggressive preprocessing if variance is low (blurry/low-quality image)
            if variance < 100:
                logger.info("Low-quality image detected, applying preprocessing")

                # Apply adaptive thresholding
                binary = cv2.adaptiveThreshold(
                    gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2
                )

                # Denoise
                denoised = cv2.fastNlMeansDenoising(binary, h=10)
                preprocessed_image = Image.fromarray(denoised)

                text_preprocessed = pytesseract.image_to_string(preprocessed_image, lang=langs, config=custom_config)

                if len(text_preprocessed.strip()) > best_length:
                    best_text = text_preprocessed
                    best_length = len(text_preprocessed.strip())
                    logger.info(f"PSM {psm} (preprocessed): extracted {len(text_preprocessed.strip())} chars")

    # Log best result
    logger.info(f"Best OCR result: {best_length} chars")
    logger.info(f"OCR extracted text:\n{best_text[:500]}..." if len(best_text) > 500 else f"OCR extracted text:\n{best_text}")

    return best_text.strip() or ""
# CLAUDE-CHECKPOINT


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
    """Health check endpoint."""
    return {
        "status": "ok"
    }


@app.post("/ocr")
async def extract_text(file: UploadFile = File(...), format: str = "text") -> JSONResponse:
    """
    Extract text from an image using OCR.

    Args:
        file: Image file to process
        format: Output format - 'text' (plain text) or 'hocr' (HTML with coordinates)

    Returns:
        - text format: Plain text extracted from image
        - hocr format: hOCR HTML with word coordinates for selectable text overlay
    """
    import logging
    logger = logging.getLogger(__name__)

    try:
        contents = await file.read()
        if not contents:
            raise HTTPException(status_code=400, detail="Empty file")

        if pytesseract is None or Image is None:
            raise HTTPException(
                status_code=500,
                detail="OCR not available on service (pytesseract/Pillow missing)",
            )

        # Open image
        original_image = Image.open(io.BytesIO(contents)).convert("RGB")

        # Get image dimensions for frontend
        width, height = original_image.size

        langs = os.getenv("OCR_LANGS", "eng")

        if format == "hocr":
            # For hOCR, use the original image without preprocessing
            # hOCR needs accurate coordinates, preprocessing can shift them
            custom_config = r'--oem 3 --psm 6 hocr'
            hocr_html = pytesseract.image_to_pdf_or_hocr(original_image, lang=langs, config=custom_config, extension='hocr')
            hocr_text = hocr_html.decode('utf-8')

            logger.info(f"hOCR extracted, length: {len(hocr_text)} chars")

            return JSONResponse(content={
                "success": True,
                "format": "hocr",
                "hocr": hocr_text,
                "image_width": width,
                "image_height": height
            })
        else:
            # Extract plain text with smart preprocessing
            # Try multiple PSM modes and keep best result
            psm_modes = [3, 6, 4]  # 3=auto, 6=uniform block, 4=single column
            best_text = ""
            best_length = 0

            for psm in psm_modes:
                custom_config = f'--oem 3 --psm {psm}'

                # First attempt: Try with original image (best for high-quality images)
                logger.info(f"Attempting OCR with original image (PSM {psm})")
                text = pytesseract.image_to_string(original_image, lang=langs, config=custom_config)

                if len(text.strip()) > best_length:
                    best_text = text
                    best_length = len(text.strip())
                    logger.info(f"PSM {psm} (original): extracted {len(text.strip())} chars")

                # Second attempt: Try with preprocessing only if numpy/cv2 available
                # Only apply preprocessing if the original attempt yielded poor results
                if np is not None and len(text.strip()) < 100:
                    import cv2
                    img_array = np.array(original_image)
                    gray = cv2.cvtColor(img_array, cv2.COLOR_RGB2GRAY)

                    # Check image quality using variance
                    variance = cv2.Laplacian(gray, cv2.CV_64F).var()
                    logger.info(f"Image variance (sharpness): {variance:.2f}")

                    # Only apply aggressive preprocessing if variance is low (blurry/low-quality image)
                    if variance < 100:
                        logger.info("Low-quality image detected, applying preprocessing")

                        # Apply adaptive thresholding
                        binary = cv2.adaptiveThreshold(
                            gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2
                        )

                        # Denoise
                        denoised = cv2.fastNlMeansDenoising(binary, h=10)
                        preprocessed_image = Image.fromarray(denoised)

                        text_preprocessed = pytesseract.image_to_string(preprocessed_image, lang=langs, config=custom_config)

                        if len(text_preprocessed.strip()) > best_length:
                            best_text = text_preprocessed
                            best_length = len(text_preprocessed.strip())
                            logger.info(f"PSM {psm} (preprocessed): extracted {len(text_preprocessed.strip())} chars")

            # Log best result
            logger.info(f"Best OCR result: {best_length} chars")
            text = best_text.strip()

            return JSONResponse(content={
                "success": True,
                "format": "text",
                "text": text,
                "length": len(text),
                "image_width": width,
                "image_height": height
            })
    # CLAUDE-CHECKPOINT

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
