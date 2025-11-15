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
    if pytesseract is None or Image is None:
        raise RuntimeError("OCR is not available (pytesseract/Pillow not installed)")

    image = Image.open(io.BytesIO(contents)).convert("RGB")
    # Convert to numpy array for potential future preprocessing
    _ = np.array(image) if np is not None else None

    langs = os.getenv("OCR_LANGS", "eng")
    text = pytesseract.image_to_string(image, lang=langs)
    return text or ""


def _parse_text_to_raw(text: str) -> Dict[str, Any]:
    """
    Very lightweight fallback parser for OCR text.

    This does NOT try to be perfect; it extracts a few key fields so that
    normalize_invoice_data() can build a consistent structure.
    """
    lines = [ln.strip() for ln in text.splitlines() if ln.strip()]
    supplier_name = lines[0] if lines else None

    # Simple date detection (YYYY-MM-DD or DD.MM.YYYY)
    date_match = re.search(r"(\d{4}-\d{2}-\d{2})", text)
    if not date_match:
        date_match = re.search(r"(\d{2}\.\d{2}\.\d{4})", text)

    invoice_date = date_match.group(1) if date_match else None

    # Find candidate total amounts (numbers with 2 decimals or whole)
    amount_pattern = re.compile(r"(\d+[.,]\d{2}|\d+)")
    numbers: List[float] = []
    for m in amount_pattern.findall(text):
        value = m.replace(",", ".")
        try:
            numbers.append(float(value))
        except ValueError:
            continue

    total_amount = max(numbers) if numbers else None

    raw: Dict[str, Any] = {
        "issuer": supplier_name,
        "invoice_number": None,
        "date": invoice_date,
        "amount": int(total_amount * 100) if total_amount is not None else None,
        "total": int(total_amount * 100) if total_amount is not None else None,
        "tax": None,
        "lines": [],
    }

    return raw


@app.get("/health")
def health() -> Dict[str, str]:
    return {"status": "ok"}


@app.post("/parse")
async def parse_invoice(file: UploadFile = File(...)) -> JSONResponse:
    if extract_data is None:
        raise HTTPException(status_code=500, detail="invoice2data library not available")

    try:
        contents = await file.read()
        if not contents:
            raise HTTPException(status_code=400, detail="Empty file")

        content_type = (file.content_type or "").lower()
        filename = (file.filename or "").lower()

        is_pdf = content_type == "application/pdf" or filename.endswith(".pdf")
        is_image = content_type.startswith("image/") or filename.endswith((".jpg", ".jpeg", ".png"))

        if is_pdf:
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

