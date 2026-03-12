"""
invoice2data microservice with smart OCR preprocessing
Version: 1.3.0 - Bank statement OCR with Gemini Vision enhancement
"""
import base64
import io
import json as json_module
import logging
import os
import re
from typing import Any, Dict, List, Optional, Tuple

import httpx

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


# ---------------------------------------------------------------------------
# Bank statement OCR helpers
# ---------------------------------------------------------------------------

# Known Macedonian bank names for auto-detection
_BANK_PATTERNS: List[Tuple[str, str]] = [
    ("комерцијална банка", "komercijalna"),
    ("komercijalna banka", "komercijalna"),
    ("стопанска банка", "stopanska"),
    ("stopanska banka", "stopanska"),
    ("нлб банка", "nlb"),
    ("nlb banka", "nlb"),
    ("халк банка", "halk"),
    ("halk bank", "halk"),
    ("прокредит банка", "procredit"),
    ("procredit bank", "procredit"),
    ("шпаркасе банка", "sparkasse"),
    ("sparkasse bank", "sparkasse"),
    ("ттк банка", "ttk"),
    ("ttk banka", "ttk"),
    ("силк роуд банка", "silkroad"),
    ("silk road bank", "silkroad"),
    ("охридска банка", "ohridska"),
    ("ohridska banka", "ohridska"),
]


def _repair_json(text: str) -> str:
    """Fix common JSON issues from LLM output (trailing commas, comments)."""
    # Remove trailing commas before } or ]
    text = re.sub(r",\s*([\]}])", r"\1", text)
    # Remove single-line comments
    text = re.sub(r"//[^\n]*", "", text)
    # Remove control characters except newlines/tabs
    text = re.sub(r"[\x00-\x08\x0b\x0c\x0e-\x1f]", "", text)
    return text


def _detect_bank_from_text(text: str) -> Tuple[Optional[str], Optional[str]]:
    """Detect bank code and name from OCR text."""
    lower = text.lower()
    for pattern, code in _BANK_PATTERNS:
        if pattern in lower:
            return code, pattern.title()
    return None, None


def _extract_statement_date(text: str) -> Optional[str]:
    """Extract statement date from header text (за ден DD.MM.YYYY)."""
    m = re.search(r"за\s+ден\s+(\d{2}[.\s]*\d{2}[.\s]*\d{4})", text)
    if m:
        raw = re.sub(r"\s+", "", m.group(1))
        # Ensure format DD.MM.YYYY
        if len(raw) == 10 and raw[2] == "." and raw[5] == ".":
            return raw
        elif len(raw) == 8:
            return f"{raw[:2]}.{raw[2:4]}.{raw[4:]}"
    # Fallback: any DD.MM.YYYY date
    m = re.search(r"(\d{2}\.\d{2}\.\d{4})", text)
    return m.group(1) if m else None


def _extract_account_number(text: str) -> Optional[str]:
    """Extract bank account number (15+ digit Macedonian format)."""
    m = re.search(r"[Сс]метка\s*[:.]?\s*(\d[\d\s]{12,})", text)
    if m:
        return re.sub(r"\s+", "", m.group(1))
    # Fallback: look for 15-16 digit numbers
    for m in re.finditer(r"\b(\d{15,16})\b", text):
        return m.group(1)
    return None


def _open_image_with_exif(contents: bytes) -> "Image":
    """Open image and auto-rotate based on EXIF orientation (phone photos)."""
    from PIL import ImageOps
    image = Image.open(io.BytesIO(contents))
    image = ImageOps.exif_transpose(image)
    return image.convert("RGB")


def _preprocess_for_table(contents: bytes) -> "Image":
    """Preprocess image for better table OCR."""
    image = _open_image_with_exif(contents)

    if np is not None:
        import cv2
        img_array = np.array(image)
        gray = cv2.cvtColor(img_array, cv2.COLOR_RGB2GRAY)

        # Check sharpness
        variance = cv2.Laplacian(gray, cv2.CV_64F).var()

        if variance < 200:
            # Sharpen slightly for phone photos
            kernel = np.array([[0, -1, 0], [-1, 5, -1], [0, -1, 0]])
            gray = cv2.filter2D(gray, -1, kernel)

        # Adaptive threshold for clean binary
        binary = cv2.adaptiveThreshold(
            gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 15, 4
        )
        return Image.fromarray(binary)

    return image


def _parse_amount(raw: str) -> float:
    """Parse amount string handling European/US formats and OCR artifacts.

    Handles garbled OCR like 10:000;00 (colon/semicolon instead of comma/period),
    trailing parentheses, and European dot-thousands (6.000.00).
    """
    s = raw.strip()
    if not s or s in ("0", "0.00", "0,00"):
        return 0.0
    # Normalize OCR-garbled separators: : → , and ; → .
    s = s.replace(":", ",").replace(";", ".")
    # Remove anything that's not digit, comma, period, minus
    s = re.sub(r"[^\d,.\-]", "", s)
    if not s:
        return 0.0

    periods = s.count(".")
    commas = s.count(",")

    # Handle multiple periods (European dot-thousands: 6.000.00)
    if periods >= 2 and commas == 0:
        parts = s.rsplit(".", 1)
        s = parts[0].replace(".", "") + "." + parts[1]
    elif commas >= 2 and periods == 0:
        parts = s.rsplit(",", 1)
        s = parts[0].replace(",", "") + "." + parts[1]
    elif commas == 1 and periods == 1:
        if s.rfind(".") > s.rfind(","):
            s = s.replace(",", "")
        else:
            s = s.replace(".", "").replace(",", ".")
    elif commas == 1 and periods == 0:
        after = s.split(",")[1]
        if len(after) <= 2:
            s = s.replace(",", ".")
        else:
            s = s.replace(",", "")
    # periods == 1 and commas == 0 → standard float

    try:
        return abs(float(s))
    except ValueError:
        return 0.0


def _group_tsv_into_lines(
    tsv_data: Dict[str, List],
) -> Tuple[List[List[Dict]], List[str]]:
    """
    Build word list from TSV data and group into visual lines.
    Returns (lines, line_texts) where each line is a list of word dicts
    sorted by X, and line_texts are the joined text per line.
    """
    n_words = len(tsv_data.get("text", []))
    if n_words == 0:
        return [], []

    words = []
    for i in range(n_words):
        text = str(tsv_data["text"][i]).strip()
        conf = int(tsv_data["conf"][i])
        if not text or conf < 10:
            continue
        words.append({
            "text": text,
            "left": int(tsv_data["left"][i]),
            "top": int(tsv_data["top"][i]),
            "width": int(tsv_data["width"][i]),
            "height": int(tsv_data["height"][i]),
            "conf": conf,
        })

    if not words:
        return [], []

    # Group by Y-coordinate clustering (proportional tolerance)
    max_top = max(w["top"] for w in words)
    line_tolerance = max(20, int(max_top * 0.012))

    words.sort(key=lambda w: (w["top"], w["left"]))

    lines: List[List[Dict]] = []
    current_line: List[Dict] = [words[0]]
    current_y = words[0]["top"]

    for w in words[1:]:
        if abs(w["top"] - current_y) < line_tolerance:
            current_line.append(w)
        else:
            lines.append(sorted(current_line, key=lambda x: x["left"]))
            current_line = [w]
            current_y = w["top"]
    if current_line:
        lines.append(sorted(current_line, key=lambda x: x["left"]))

    line_texts = [" ".join(w["text"] for w in lw) for lw in lines]
    return lines, line_texts


def _extract_table_from_tsv(
    tsv_data: Dict[str, List], image_width: int, own_account: Optional[str] = None
) -> Tuple[List[Dict[str, Any]], List[str]]:
    """
    Extract table rows from Tesseract TSV output (image_to_data).
    Groups words by line, then maps to columns using X-position heuristics.

    Returns (transactions, debug_line_texts) — debug_line_texts is the
    reconstructed text for each visual line (useful for diagnostics).
    """
    logger = logging.getLogger(__name__)

    lines, line_texts = _group_tsv_into_lines(tsv_data)
    if not lines:
        return [], []

    logger.info(f"Detected {len(lines)} visual lines from TSV")
    # Log first 30 lines for debugging
    for i, lt in enumerate(line_texts[:30]):
        logger.info(f"  TSV line [{i}]: {lt[:120]}")

    # --- Find table header row ---
    header_idx = -1
    totals_idx = len(lines)

    # Header detection keywords (checked on each line)
    _HEADER_KWS = ["р.бр", "побарува", "должи", "сметка", "дознака", "примач"]

    for i, text in enumerate(line_texts):
        lower = text.lower()

        # Primary: exact Р.бр. match
        if "р.бр" in lower or "р. бр" in lower:
            header_idx = i

        # Secondary: line containing 2+ header keywords
        if header_idx < 0:
            hits = sum(1 for kw in _HEADER_KWS if kw in lower)
            if hits >= 2:
                header_idx = i

        # Totals line
        if "вкупно" in lower and header_idx >= 0:
            totals_idx = i
            break

    # Tertiary: check consecutive line pairs for 2+ keywords
    if header_idx < 0:
        for i in range(len(line_texts) - 1):
            combined = (line_texts[i] + " " + line_texts[i + 1]).lower()
            hits = sum(1 for kw in _HEADER_KWS if kw in combined)
            if hits >= 2:
                header_idx = i + 1
                break

    if header_idx >= 0:
        logger.info(f"Table header at line {header_idx}, totals at {totals_idx}")

        header_words = lines[header_idx]
        col_positions = _detect_columns_from_header(header_words, image_width)
        logger.info(f"Detected column positions: {col_positions}")

        transactions = []
        start_idx = header_idx + 1
        for i in range(header_idx + 1, min(header_idx + 4, totals_idx)):
            lower = line_texts[i].lower()
            if "должи" in lower or "побарува" in lower or "износ" in lower:
                start_idx = i + 1

        # Group lines into multi-line transaction blocks.
        # A new transaction starts when a line begins with:
        # - a digit (row number)
        # - > or | (OCR-garbled row numbers)
        tx_blocks: List[List[int]] = []
        current_block: List[int] = []

        for i in range(start_idx, totals_idx):
            text = line_texts[i].strip()
            if not text or len(text) < 3:
                continue
            first_word = lines[i][0]["text"].strip()
            is_new_tx = (
                first_word.isdigit()
                or first_word in (">", "|", "Т")
            )
            if is_new_tx and current_block:
                tx_blocks.append(current_block)
                current_block = [i]
            else:
                current_block.append(i)
        if current_block:
            tx_blocks.append(current_block)

        logger.info(f"Header-based: {len(tx_blocks)} transaction blocks")

        # Process each block: merge all words, then map to columns
        for block in tx_blocks:
            all_words = []
            for bi in block:
                all_words.extend(lines[bi])
            tx = _map_words_to_columns(all_words, col_positions, image_width)
            first_word = lines[block[0]][0]["text"].strip()
            tx["row_number"] = (
                int(first_word) if first_word.isdigit()
                else len(transactions) + 1
            )
            transactions.append(tx)

        logger.info(f"Header-based extraction: {len(transactions)} transactions")

        # Quality check: reject if transactions look like noise
        if transactions:
            has_account = any(tx.get("counterparty_account") for tx in transactions)
            max_amount = max(
                max(tx.get("debit", 0), tx.get("credit", 0))
                for tx in transactions
            )
            if has_account or max_amount >= 100:
                return transactions, line_texts
            logger.info(
                f"Rejecting header-based results: no accounts, max_amount={max_amount}"
            )

    # --- Header-free fallback: find rows with account numbers + amounts ---
    logger.info("Header not found or header extraction empty; trying header-free")
    return _extract_table_without_header(lines, line_texts, image_width, own_account), line_texts


def _extract_table_without_header(
    lines: List[List[Dict]],
    line_texts: List[str],
    image_width: int,
    own_account: Optional[str] = None,
) -> List[Dict[str, Any]]:
    """
    Extract transactions from TSV lines WITHOUT relying on header detection.

    Strategy: find lines containing bank account numbers (15+ digits) and
    amounts. Use X-positions to assign amounts to debit/credit columns.
    Lines with accounts but no amounts get amounts from continuation lines.
    """
    logger = logging.getLogger(__name__)

    account_re = re.compile(r"^\d{15,16}$")
    # Words that look like amounts: digits with comma/period thousands/decimals
    # Match amount-like words: 10,000.00, 0.00, 6.000,00, 10:000;00 (OCR garbled)
    amount_word_re = re.compile(r"^\d{1,3}(?:[,.:;]\d{3})*[,.:;]\d{2}\)?$")

    # Identify transaction-bearing lines: lines with a 15-16 digit number
    tx_line_indices: List[int] = []
    for i, lw in enumerate(lines):
        for w in lw:
            wt = w["text"].replace(" ", "")
            if account_re.match(wt) and wt != own_account:
                tx_line_indices.append(i)
                break

    logger.info(f"Header-free: found {len(tx_line_indices)} lines with accounts")

    if not tx_line_indices:
        return []

    # For debit/credit column discrimination, find the X positions of
    # amount-like words across ALL transaction lines
    amount_x_positions: List[int] = []
    for i in tx_line_indices:
        for w in lines[i]:
            if amount_word_re.match(w["text"]):
                amount_x_positions.append(w["left"])

    # Also collect from lines immediately after transaction lines (continuation)
    for i in tx_line_indices:
        for j in range(i + 1, min(i + 3, len(lines))):
            if j in tx_line_indices:
                break
            for w in lines[j]:
                if amount_word_re.match(w["text"]):
                    amount_x_positions.append(w["left"])

    if not amount_x_positions:
        logger.info("Header-free: no amount words found")
        return []

    # Cluster amount X positions to find debit and credit column centers
    amount_x_positions.sort()
    # Simple clustering: split into 2 groups using the largest gap
    debit_x = amount_x_positions[0]
    credit_x = amount_x_positions[-1]
    if len(amount_x_positions) >= 2:
        max_gap = 0
        split_at = 0
        for k in range(len(amount_x_positions) - 1):
            gap = amount_x_positions[k + 1] - amount_x_positions[k]
            if gap > max_gap:
                max_gap = gap
                split_at = k
        if max_gap > image_width * 0.03:  # meaningful gap
            group1 = amount_x_positions[: split_at + 1]
            group2 = amount_x_positions[split_at + 1 :]
            debit_x = sum(group1) / len(group1)
            credit_x = sum(group2) / len(group2)
            logger.info(
                f"Amount columns: debit ~{debit_x:.0f}px, credit ~{credit_x:.0f}px"
            )

    mid_x = (debit_x + credit_x) / 2 if debit_x != credit_x else image_width * 0.5

    # Build transactions
    transactions: List[Dict[str, Any]] = []

    for idx, tx_i in enumerate(tx_line_indices):
        lw = lines[tx_i]
        text = line_texts[tx_i]

        # Find account
        account = None
        acct_x = 0
        for w in lw:
            wt = w["text"].replace(" ", "")
            if account_re.match(wt) and wt != own_account:
                account = wt
                acct_x = w["left"]
                break

        # Find amounts on this line + continuation lines
        amounts_with_x: List[Tuple[int, float]] = []

        # Context: this line + up to 2 continuation lines before next tx
        end_line = tx_line_indices[idx + 1] if idx + 1 < len(tx_line_indices) else min(tx_i + 4, len(lines))
        for j in range(tx_i, end_line):
            for w in lines[j]:
                if amount_word_re.match(w["text"]):
                    val = _parse_amount(w["text"])
                    if val >= 0:
                        amounts_with_x.append((w["left"], val))

        # Assign to debit/credit based on X position
        debit = 0.0
        credit = 0.0
        for ax, av in amounts_with_x:
            if av == 0:
                continue
            if ax < mid_x:
                debit = max(debit, av)  # take largest if multiple
            else:
                credit = max(credit, av)

        # If only one non-zero amount and it's unclear, check for zero words
        if debit == 0 and credit == 0 and amounts_with_x:
            non_zero = [(ax, av) for ax, av in amounts_with_x if av > 0]
            if non_zero:
                credit = non_zero[0][1]  # default to credit

        if debit == 0 and credit == 0:
            continue

        # Counterparty: words to the left of account
        name_words = []
        for w in lw:
            if w["left"] < acct_x:
                wt = w["text"].strip()
                if not wt.isdigit() or len(wt) > 2:
                    name_words.append(wt)
        # Remove the row number (first single digit)
        if name_words and name_words[0].isdigit() and len(name_words[0]) <= 2:
            row_num_str = name_words.pop(0)
        else:
            row_num_str = None

        counterparty = " ".join(name_words).strip()

        # Continuation lines may add to name/description
        description_parts = []
        for j in range(tx_i + 1, end_line):
            if j in tx_line_indices:
                break
            cont_text = line_texts[j].strip()
            if cont_text and not re.match(r"^[\d.,\s]+$", cont_text):
                description_parts.append(cont_text)

        # Reference: 6-7 digit numbers in context
        reference = None
        context_text = " ".join(line_texts[tx_i:end_line])
        for rm in re.finditer(r"\b(\d{6,7})\b", context_text):
            rv = rm.group(1)
            if account and rv in account:
                continue
            if own_account and rv in own_account:
                continue
            reference = rv
            break

        # Payment code
        payment_code = None
        for cm in re.finditer(r"\b(\d{3})\b", context_text):
            val = int(cm.group(1))
            if 100 <= val <= 999:
                payment_code = val
                break

        tx = {
            "row_number": len(transactions) + 1,
            "counterparty_name": counterparty or None,
            "counterparty_account": account,
            "debit": debit,
            "credit": credit,
            "payment_code": payment_code,
            "description": " ".join(description_parts).strip() or None,
            "reference": reference,
            "pbo": None,
        }
        transactions.append(tx)
        logger.info(
            f"  HdrFree TX {tx['row_number']}: acct={account} "
            f"debit={debit} credit={credit} name={counterparty[:40]}"
        )

    logger.info(f"Header-free extraction: {len(transactions)} transactions")
    return transactions


def _detect_columns_from_header(header_words: List[Dict], image_width: int) -> Dict[str, int]:
    """
    Detect column X-positions from header word positions.
    Returns dict with column_name -> left_x_position.
    """
    cols: Dict[str, int] = {}

    for w in header_words:
        text = w["text"].lower()
        x = w["left"]

        if "р.бр" in text or text == "р.бр." or text == "р.":
            cols["row_num"] = x
        elif "назив" in text or "примач" in text or "наплатогодавач" in text:
            cols.setdefault("counterparty", x)
        elif "сметка" in text:
            cols.setdefault("account", x)
        elif "начин" in text:
            cols["method"] = x
        elif "должи" in text:
            cols["debit"] = x
        elif "побарува" in text:
            cols["credit"] = x
        elif "пров" in text:
            cols["fee"] = x
        elif "шиф" in text:
            cols["code"] = x
        elif "цел" in text or "дознака" in text:
            cols.setdefault("description", x)
        elif "пбо" in text or "пбз" in text:
            cols["pbo"] = x
        elif "податок" in text or "рекламација" in text:
            cols.setdefault("reference", x)

    # If we couldn't detect column positions from headers, use proportional defaults
    if "debit" not in cols and "credit" not in cols:
        cols = {
            "row_num": 0,
            "counterparty": int(image_width * 0.04),
            "account": int(image_width * 0.16),
            "method": int(image_width * 0.28),
            "debit": int(image_width * 0.32),
            "credit": int(image_width * 0.42),
            "fee": int(image_width * 0.52),
            "code": int(image_width * 0.60),
            "description": int(image_width * 0.64),
            "pbo": int(image_width * 0.82),
            "reference": int(image_width * 0.88),
        }

    return cols


def _map_words_to_columns(
    line_words: List[Dict], col_positions: Dict[str, int], image_width: int
) -> Dict[str, Any]:
    """Map words in a line to their respective columns based on X positions."""
    # Sort column boundaries
    col_names = sorted(col_positions.keys(), key=lambda k: col_positions[k])
    col_xs = [col_positions[k] for k in col_names]

    # Add end boundary
    col_xs.append(image_width)
    col_names_list = list(col_names)

    # Assign each word to a column
    column_words: Dict[str, List[str]] = {name: [] for name in col_names_list}

    for w in line_words:
        word_center = w["left"] + w["width"] // 2
        assigned = col_names_list[-1]  # default: last column

        for j in range(len(col_xs) - 1):
            # Allow some tolerance (20px left of column start)
            left_bound = col_xs[j] - 20
            right_bound = col_xs[j + 1] - 20 if j + 1 < len(col_xs) - 1 else image_width
            if left_bound <= word_center < right_bound:
                assigned = col_names_list[j]
                break

        column_words[assigned].append(w["text"])

    # Build transaction dict
    counterparty = " ".join(column_words.get("counterparty", []))
    account = " ".join(column_words.get("account", [])).replace(" ", "")
    debit_str = " ".join(column_words.get("debit", []))
    credit_str = " ".join(column_words.get("credit", []))
    code_str = " ".join(column_words.get("code", []))
    description = " ".join(column_words.get("description", []))
    reference = " ".join(column_words.get("reference", []))
    pbo = " ".join(column_words.get("pbo", []))

    debit = _parse_amount(debit_str)
    credit = _parse_amount(credit_str)

    # Extract payment code (3-digit number)
    code_match = re.search(r"\d{3}", code_str)
    payment_code = int(code_match.group()) if code_match else None

    return {
        "counterparty_name": counterparty.strip() or None,
        "counterparty_account": account if len(account) >= 9 else None,
        "debit": debit,
        "credit": credit,
        "payment_code": payment_code,
        "description": description.strip() or None,
        "reference": reference.strip() or None,
        "pbo": pbo.strip() or None,
    }


def _parse_amount_flexible(raw: str) -> float:
    """Parse amount handling European formats and OCR artifacts.

    Handles: 10,000.00 | 6.000,00 | 6.000.00 | 12,100.00 | 0.00
    Also strips OCR noise like trailing parentheses or letters.
    """
    s = raw.strip()
    if not s:
        return 0.0
    # Remove anything that's not digit, comma, period, minus
    s = re.sub(r"[^\d,.\-]", "", s)
    if not s or s in ("0", "0.00", "0,00"):
        return 0.0

    periods = s.count(".")
    commas = s.count(",")

    if periods >= 2 and commas == 0:
        # European dot-thousands: 6.000.00 → last period is decimal
        parts = s.rsplit(".", 1)
        s = parts[0].replace(".", "") + "." + parts[1]
    elif commas >= 2 and periods == 0:
        # 6,000,00 format
        parts = s.rsplit(",", 1)
        s = parts[0].replace(",", "") + "." + parts[1]
    elif commas == 1 and periods == 1:
        if s.rfind(".") > s.rfind(","):
            s = s.replace(",", "")  # 10,000.00
        else:
            s = s.replace(".", "").replace(",", ".")  # 10.000,00
    elif commas == 1 and periods == 0:
        after = s.split(",")[1]
        if len(after) <= 2:
            s = s.replace(",", ".")  # 6,00 → 6.00
        else:
            s = s.replace(",", "")  # 6,000 → 6000
    # periods == 1 and commas == 0 → standard float

    try:
        return abs(float(s))
    except ValueError:
        return 0.0


def _extract_transactions_from_text(plain_text: str) -> List[Dict[str, Any]]:
    """
    Extract transactions from plain OCR text of a Macedonian bank statement.

    Uses multiple signals instead of relying only on account numbers:
    - Row numbers at line starts (primary transaction delimiter)
    - Amount patterns (most reliably OCR'd feature)
    - Account numbers when available (optional)
    - Cyrillic zero indicators (Ој, О = OCR'd 0)
    """
    logger = logging.getLogger(__name__)
    logger.info("Attempting plain-text transaction extraction (multi-signal)")

    lines = plain_text.splitlines()

    # Detect company's own account from header
    own_account = None
    own_match = re.search(r"[Сс]метка\s*[:.]?\s*(\d[\d ]{12,})", plain_text)
    if own_match:
        own_account = re.sub(r"\s+", "", own_match.group(1))
        logger.info(f"Company's own account: {own_account}")

    # Find transaction area boundaries
    header_idx = -1
    totals_idx = len(lines)
    for i, line in enumerate(lines):
        lower = line.lower().strip()
        if "р.бр" in lower or "р. бр" in lower:
            header_idx = i
        if header_idx >= 0 and ("вкупно" in lower or "салдо" in lower):
            totals_idx = i
            break

    # If no explicit header found, detect first transaction-like line
    if header_idx < 0:
        for i, line in enumerate(lines):
            if re.match(r"^\s*1\s+\S", line) and re.search(
                r"\d{1,3}(?:,\d{3})*\.\d{2}", line
            ):
                header_idx = i - 1
                break

    if header_idx < 0:
        logger.warning("Could not find transaction area in OCR text")
        return []

    # Skip sub-header rows (должи/побарува column labels)
    start_idx = header_idx + 1
    for i in range(header_idx + 1, min(header_idx + 5, totals_idx)):
        if i < len(lines):
            lower = lines[i].lower().strip()
            if any(
                kw in lower
                for kw in ["должи", "побарува", "износ", "нач.пл", "нач. пл"]
            ):
                start_idx = i + 1

    tx_lines = lines[start_idx:totals_idx]
    logger.info(
        f"Transaction area: lines {start_idx}-{totals_idx} ({len(tx_lines)} lines)"
    )
    for i, l in enumerate(tx_lines):
        logger.info(f"  TX area [{i}]: {l.rstrip()[:120]}")

    # --- Patterns ---
    # Standard amounts: 10,000.00 or 0.00
    # European dot-thousands: 6.000.00 or 6.000,00
    amount_re = re.compile(
        r"(\d{1,3}(?:,\d{3})*\.\d{2})"  # 10,000.00
        r"|(\d{1,3}(?:\.\d{3})+[.,]\d{2})"  # 6.000.00 or 6.000,00
    )
    # Account: 10+ digit sequences (relaxed from 15)
    account_re = re.compile(r"\b(\d{15,16})\b|\b(\d{8,12})\s(\d{3,6})\b")
    # Cyrillic zero indicators (OCR reads 0 as О, 0.00 as ој)
    zero_re = re.compile(r"\bОј\b|\bОј\b|\b[Оо]j\b|\b0[.,]00\b|\b0\b")

    # --- Segment lines into transaction blocks ---
    # A new transaction starts when a line begins with a row number (single digit)
    # or a garbled row number (>, |) followed by content with amounts.
    segments: List[List[str]] = []
    current: List[str] = []

    for line in tx_lines:
        stripped = line.strip()
        if not stripped:
            continue

        is_new = False

        # Line starts with single digit 1-9 followed by space + content
        row_match = re.match(r"^\s*(\d)\s+\S", stripped)
        if row_match:
            digit = int(row_match.group(1))
            rest = stripped[row_match.end(1) :].strip()
            if 1 <= digit <= 9 and len(rest) > 5:
                is_new = True

        # OCR-garbled row numbers: > (for 2), | (for 1)
        if not is_new and re.match(r"^\s*[>|]\s+\S", stripped):
            if amount_re.search(stripped) or account_re.search(stripped):
                is_new = True

        # Line with account + amount that doesn't look like header
        if not is_new and account_re.search(stripped) and amount_re.search(stripped):
            for m in account_re.finditer(stripped):
                acct = re.sub(
                    r"\s+",
                    "",
                    m.group(1) or (m.group(2) + m.group(3)),
                )
                if acct != own_account and len(acct) >= 10:
                    is_new = True
                    break

        if is_new and current:
            segments.append(current)
            current = [stripped]
        else:
            current.append(stripped)

    if current:
        segments.append(current)

    logger.info(f"Segmented into {len(segments)} transaction blocks")
    for i, seg in enumerate(segments):
        logger.info(f"  Seg[{i}]: {' | '.join(s[:80] for s in seg)}")

    # --- Merge amount-less trailing segments into previous segment ---
    # e.g. "5 ДООЕЛ Скопје основ на инве" has no amount → merge with prev
    merged: List[List[str]] = []
    for seg in segments:
        block_text = " ".join(seg)
        has_amount = bool(amount_re.search(block_text))
        # Also count Cyrillic zero + any amount as having amounts
        has_zero_and_amount = bool(zero_re.search(block_text)) and has_amount

        if not has_amount and merged:
            # Merge with previous segment
            merged[-1].extend(seg)
        else:
            merged.append(seg)

    if len(merged) != len(segments):
        logger.info(
            f"After merging amount-less segments: {len(merged)} blocks"
        )
    segments = merged

    # --- Parse each segment into a transaction ---
    transactions: List[Dict[str, Any]] = []

    for seg_idx, segment in enumerate(segments):
        block_text = " ".join(segment)
        first_line = segment[0]

        # Extract all amounts from the entire block
        all_amounts: List[float] = []
        for m in amount_re.finditer(block_text):
            raw = m.group(1) or m.group(2)
            val = _parse_amount_flexible(raw)
            all_amounts.append(val)

        # Extract amounts from first line only (for debit/credit column order)
        first_amounts: List[float] = []
        for m in amount_re.finditer(first_line):
            raw = m.group(1) or m.group(2)
            first_amounts.append(_parse_amount_flexible(raw))

        # Check for zero indicators on first line
        has_zero_first = bool(zero_re.search(first_line))

        if not all_amounts and not has_zero_first:
            logger.info(f"  Seg[{seg_idx}]: no amounts or zeros, skipping")
            continue

        # Extract account number (optional - don't require it)
        account = None
        for m in account_re.finditer(block_text):
            acct = re.sub(
                r"\s+",
                "",
                m.group(1) or (m.group(2) + m.group(3)),
            )
            if acct != own_account:
                account = acct
                break

        # --- Determine debit / credit ---
        debit = 0.0
        credit = 0.0

        if len(first_amounts) >= 2:
            # Two amounts on first line → debit column, then credit column
            debit = first_amounts[0]
            credit = first_amounts[1]
        elif len(first_amounts) == 1:
            amt = first_amounts[0]
            if amt > 0:
                # Check relative position of amount vs zero indicator
                amt_match = amount_re.search(first_line)
                if amt_match:
                    after_amt = first_line[amt_match.end() :].strip()
                    before_amt = first_line[: amt_match.start()].strip()
                    if zero_re.search(after_amt):
                        # amount then zero → debit
                        debit = amt
                    elif zero_re.search(before_amt) or has_zero_first:
                        # zero then amount → credit
                        credit = amt
                    else:
                        # No clear indicator; check continuation lines
                        rest_text = " ".join(segment[1:])
                        rest_amounts = [
                            _parse_amount_flexible(m.group(1) or m.group(2))
                            for m in amount_re.finditer(rest_text)
                        ]
                        if rest_amounts and rest_amounts[0] == 0:
                            debit = amt
                        elif rest_amounts and amt == 0:
                            credit = rest_amounts[0]
                        else:
                            credit = amt  # default
            elif has_zero_first:
                # First amount is 0, look for non-zero in block
                non_zero = [a for a in all_amounts if a > 0]
                if non_zero:
                    credit = non_zero[0]
        elif has_zero_first and all_amounts:
            # First line has zero indicator, amounts in continuation lines
            non_zero = [a for a in all_amounts if a > 0]
            if non_zero:
                credit = non_zero[0]
        elif all_amounts:
            # Amounts only in continuation lines
            if len(all_amounts) >= 2:
                debit = all_amounts[0]
                credit = all_amounts[1]
            else:
                credit = all_amounts[0]

        if debit == 0 and credit == 0:
            logger.info(f"  Seg[{seg_idx}]: zero amounts, skipping")
            continue

        # --- Counterparty name ---
        name_line = first_line
        # Strip leading row number or garbled row number
        name_line = re.sub(r"^\s*\d\s+", "", name_line)
        name_line = re.sub(r"^\s*[>|]\s+", "", name_line)
        # Take text before first account or amount
        name_end = len(name_line)
        for pat in [account_re, amount_re]:
            m = pat.search(name_line)
            if m and m.start() < name_end:
                name_end = m.start()
        # Also stop at zero indicator
        zm = zero_re.search(name_line)
        if zm and zm.start() < name_end:
            name_end = zm.start()
        name = name_line[:name_end].strip()
        name = re.sub(r"[|>~]", "", name).strip()

        # Gather continuation name fragments
        for seg_line in segment[1:]:
            # Only take name-like lines: no amounts, has Cyrillic/Latin text
            if not amount_re.search(seg_line):
                clean = re.sub(r"[|>~]", "", seg_line).strip()
                clean = re.sub(r"^\d+\s+", "", clean).strip()
                if clean and len(clean) > 2 and not re.match(
                    r"^[\d.,\s]+$", clean
                ):
                    name = (name + " " + clean).strip()
            else:
                # Lines with amounts may have name at start
                m = amount_re.search(seg_line)
                if m:
                    before = seg_line[: m.start()].strip()
                    before = re.sub(r"^\d+\s+", "", before).strip()
                    before = re.sub(r"[|>~]", "", before).strip()
                    if before and len(before) > 2:
                        name = (name + " " + before).strip()

        name = re.sub(r"\s+", " ", name).strip()

        # --- Payment code (3-digit, 100-999) ---
        payment_code = None
        for cm in re.finditer(r"\b(\d{3})\b", block_text):
            val = int(cm.group(1))
            if 100 <= val <= 999:
                payment_code = val
                break

        # --- Reference number (6-7 digits) ---
        reference = None
        for rm in re.finditer(r"\b(\d{6,7})\b", block_text):
            ref_val = rm.group(1)
            # Skip if it's part of an account number
            if account and ref_val in account:
                continue
            if own_account and ref_val in own_account:
                continue
            reference = ref_val
            break

        # --- Description ---
        desc_keywords = [
            "промет", "основ", "услуг", "плаќање", "месец",
            "правни", "извр", "произ", "стоки", "инве", "сметководствен",
        ]
        description_parts = []
        for seg_line in segment:
            if any(kw in seg_line.lower() for kw in desc_keywords):
                clean = seg_line.strip()
                clean = re.sub(r"\d{15,}", "", clean)
                clean = re.sub(r"\d{1,3}(?:,\d{3})*\.\d{2}", "", clean)
                clean = re.sub(r"[|>~]", "", clean).strip()
                if clean and len(clean) > 3:
                    description_parts.append(clean)
        description = " ".join(description_parts).strip()

        tx = {
            "row_number": len(transactions) + 1,
            "counterparty_name": name or None,
            "counterparty_account": account,
            "debit": debit,
            "credit": credit,
            "payment_code": payment_code,
            "description": description or None,
            "reference": reference,
            "pbo": None,
        }
        transactions.append(tx)
        logger.info(
            f"  TX {tx['row_number']}: acct={account} "
            f"debit={debit} credit={credit} "
            f"name={name[:40]} ref={reference}"
        )

    logger.info(f"Plain-text extraction found {len(transactions)} transactions")
    return transactions


# ---------------------------------------------------------------------------
# Gemini Vision enhancement
# ---------------------------------------------------------------------------

_GEMINI_PROMPT = """This is a Macedonian bank statement image. Extract ALL transactions from the table.

Return a JSON array where each element has exactly these fields:
- row_number: integer (sequential, starting from 1)
- counterparty_name: string (full company/person name)
- counterparty_account: string (15-16 digit bank account number)
- debit: number (amount debited/withdrawn, 0.00 if none)
- credit: number (amount credited/received, 0.00 if none)
- payment_code: integer (3-digit payment code, null if not visible)
- description: string (purpose of payment/transfer)
- reference: string (6-7 digit reference number, null if not visible)

IMPORTANT:
- Extract EVERY transaction row, do not skip any
- Amounts must be numeric (e.g. 10000.00 not "10,000.00")
- Account numbers are 15-16 digits, do not truncate
- Return ONLY the raw JSON array, no markdown code fences, no explanation"""


async def _extract_with_gemini(
    contents: bytes, mime_type: str = "image/jpeg"
) -> Optional[List[Dict[str, Any]]]:
    """
    Use Gemini Vision to extract transactions from bank statement image.
    Returns list of transaction dicts, or None if Gemini is unavailable/fails.
    """
    logger = logging.getLogger(__name__)

    api_key = os.getenv("GEMINI_API_KEY")
    if not api_key:
        logger.info("GEMINI_API_KEY not set, skipping Gemini extraction")
        return None

    model = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")
    url = (
        f"https://generativelanguage.googleapis.com/v1beta/models/"
        f"{model}:generateContent?key={api_key}"
    )

    img_b64 = base64.b64encode(contents).decode("utf-8")

    payload = {
        "contents": [
            {
                "parts": [
                    {"text": _GEMINI_PROMPT},
                    {
                        "inline_data": {
                            "mime_type": mime_type,
                            "data": img_b64,
                        }
                    },
                ]
            }
        ],
        "generationConfig": {
            "temperature": 0.1,
            "maxOutputTokens": 16384,
            "responseMimeType": "application/json",
        },
    }

    try:
        async with httpx.AsyncClient(timeout=60.0) as client:
            resp = await client.post(
                url,
                json=payload,
                headers={"Content-Type": "application/json"},
            )

        if resp.status_code != 200:
            logger.warning(
                f"Gemini API error {resp.status_code}: {resp.text[:200]}"
            )
            return None

        data = resp.json()
        if "error" in data:
            logger.warning(f"Gemini error: {data['error'].get('message', '')}")
            return None

        # Extract text from response
        text = (
            data.get("candidates", [{}])[0]
            .get("content", {})
            .get("parts", [{}])[0]
            .get("text", "")
        )

        if not text:
            logger.warning("Gemini returned empty text")
            return None

        # Strip markdown code fences if present
        text = text.strip()
        if text.startswith("```"):
            text = re.sub(r"^```\w*\n?", "", text)
            text = re.sub(r"\n?```$", "", text)
            text = text.strip()

        # Parse JSON (with repair for trailing commas etc.)
        text = _repair_json(text)
        transactions = json_module.loads(text)

        if not isinstance(transactions, list):
            logger.warning(f"Gemini returned non-list: {type(transactions)}")
            return None

        # Normalize fields
        for tx in transactions:
            tx["debit"] = float(tx.get("debit") or 0)
            tx["credit"] = float(tx.get("credit") or 0)
            tx["row_number"] = int(tx.get("row_number") or 0)
            # Ensure account is string
            if tx.get("counterparty_account"):
                tx["counterparty_account"] = str(tx["counterparty_account"])
            # Add pbo field for compatibility
            tx.setdefault("pbo", None)

        logger.info(f"Gemini extracted {len(transactions)} transactions")
        for tx in transactions:
            logger.info(
                f"  Gemini TX{tx['row_number']}: "
                f"acct={tx.get('counterparty_account')} "
                f"d={tx['debit']} c={tx['credit']} "
                f"name={str(tx.get('counterparty_name', ''))[:40]}"
            )

        return transactions

    except json_module.JSONDecodeError as e:
        logger.warning(f"Gemini JSON parse error: {e}")
        return None
    except httpx.TimeoutException:
        logger.warning("Gemini request timed out")
        return None
    except Exception as e:
        logger.warning(f"Gemini extraction failed: {e}")
        return None
_GEMINI_INVOICE_PROMPT = """This is an invoice or receipt image (possibly in Macedonian/Cyrillic).
Extract the following information and return it as a single JSON object:

{
  "issuer": "company/person name that issued the invoice",
  "tax_id": "VAT/tax ID of the issuer (e.g. MK4030996116740)",
  "address": "issuer address",
  "invoice_number": "invoice number/ID (look for Фактура No, Фактура Бр., Број, Invoice No, F-number, etc.)",
  "date": "invoice date in YYYY-MM-DD format",
  "due_date": "payment due date in YYYY-MM-DD format, null if not visible",
  "currency": "currency code (MKD, EUR, USD, etc.)",
  "amount": total amount as a number (e.g. 11800.00),
  "subtotal": subtotal/net amount before tax as a number,
  "tax": total tax amount as a number,
  "lines": [
    {
      "description": "item/service description",
      "quantity": actual quantity as number (default 1 if not clearly a quantity column),
      "unit_price": unit price as number,
      "tax": tax amount for this line as number (0 if not taxed),
      "total": line total as number
    }
  ]
}

STEPS:
1. FIRST look for a payment slip / payment amount on the invoice. Look for "Износ за плаќање", "Износот за плаќање", "За плаќање", "Вкупно за плаќање", "Total due", or a prominent total amount, often on a tear-off payment slip section. This is the DEFINITIVE grand total — the actual amount the recipient must pay.
2. Read ALL column headers in the table to understand what each column represents.
3. Identify which column is description, which is quantity, which is unit price, which is tax, which is the line total.
4. Only map a column to "quantity" if its header clearly means quantity/count of items PURCHASED (e.g. "количина", "qty", "кол."). Do NOT use columns like number of apartments/units (бр. на станари, единици), row numbers, codes, or allocation counts as quantity.
5. If the table has multiple numeric columns, find the column whose values sum closest to the payment slip grand total. Use THAT column for line "total" values.
6. Extract line item values according to the identified columns.

IMPORTANT:
- All monetary amounts must be numeric (e.g. 11800.00 not "11,800.00")
- Amounts are in the smallest visible unit (if the invoice shows 11,800 MKD, return 11800.00)
- European number format: dot (.) is thousands separator, comma (,) is decimal separator. "1.440,00" = 1440.00, "144,00" = 144.00
- Extract ALL line items from the table
- If no column header clearly indicates quantity, set quantity to 1 for all lines
- CRITICAL: The "amount" (grand total) MUST be the payment slip amount — the actual amount the invoice recipient owes. For building management invoices (ХАБИДОМ, управител, одржување на зграда), this is the PER-APARTMENT amount, not the total for all apartments.
- CRITICAL: The sum of all line "total" values must approximately equal the "amount" (grand total). If they don't match, you picked the wrong column — find the column whose values sum to the grand total.
- Return ONLY the raw JSON object, no markdown code fences, no explanation
- If a field is not visible, use null
- Ensure all string values are properly escaped (no unescaped quotes or special characters)"""


def _resize_image_for_gemini(contents: bytes, max_dimension: int = 1600) -> bytes:
    """
    Resize image to max_dimension on longest side to reduce Gemini API latency.
    A 4MB photo becomes ~300-500KB, dramatically faster to upload and process.
    """
    logger = logging.getLogger(__name__)
    try:
        if Image is None:
            return contents

        img = Image.open(io.BytesIO(contents))
        w, h = img.size

        if max(w, h) <= max_dimension:
            logger.info(f"Image {w}x{h} already within {max_dimension}px, no resize needed")
            return contents

        ratio = max_dimension / max(w, h)
        new_w, new_h = int(w * ratio), int(h * ratio)
        img = img.resize((new_w, new_h), Image.LANCZOS)

        buf = io.BytesIO()
        fmt = "PNG" if img.mode == "RGBA" else "JPEG"
        img.save(buf, format=fmt, quality=85)
        resized = buf.getvalue()

        logger.info(
            f"Resized image {w}x{h} ({len(contents)//1024}KB) -> "
            f"{new_w}x{new_h} ({len(resized)//1024}KB)"
        )
        return resized
    except Exception as e:
        logger.warning(f"Image resize failed, using original: {e}")
        return contents


async def _extract_invoice_with_gemini(
    contents: bytes, mime_type: str = "image/jpeg"
) -> Optional[Dict[str, Any]]:
    """
    Use Gemini Vision to extract invoice data from an image.
    Returns a raw dict compatible with normalize_invoice_data(), or None.
    """
    logger = logging.getLogger(__name__)

    api_key = os.getenv("GEMINI_API_KEY")
    if not api_key:
        logger.info("GEMINI_API_KEY not set, skipping Gemini invoice extraction")
        return None

    # Resize large images to reduce upload size and Gemini processing time
    contents = _resize_image_for_gemini(contents)
    if mime_type == "image/png" and not contents[:4] == b'\x89PNG':
        mime_type = "image/jpeg"  # resize may have converted PNG to JPEG

    model = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")
    url = (
        f"https://generativelanguage.googleapis.com/v1beta/models/"
        f"{model}:generateContent?key={api_key}"
    )

    img_b64 = base64.b64encode(contents).decode("utf-8")

    payload = {
        "contents": [
            {
                "parts": [
                    {"text": _GEMINI_INVOICE_PROMPT},
                    {
                        "inline_data": {
                            "mime_type": mime_type,
                            "data": img_b64,
                        }
                    },
                ]
            }
        ],
        "generationConfig": {
            "temperature": 0.1,
            "maxOutputTokens": 16384,
            "responseMimeType": "application/json",
        },
    }

    try:
        async with httpx.AsyncClient(timeout=60.0) as client:
            resp = await client.post(
                url,
                json=payload,
                headers={"Content-Type": "application/json"},
            )

        if resp.status_code != 200:
            logger.warning(
                f"Gemini invoice API error {resp.status_code}: {resp.text[:200]}"
            )
            return None

        data = resp.json()
        if "error" in data:
            logger.warning(f"Gemini error: {data['error'].get('message', '')}")
            return None

        # Check finish reason for truncation
        candidate = data.get("candidates", [{}])[0]
        finish_reason = candidate.get("finishReason", "UNKNOWN")
        logger.info(f"Gemini invoice finish_reason: {finish_reason}")

        text = (
            candidate
            .get("content", {})
            .get("parts", [{}])[0]
            .get("text", "")
        )

        if not text:
            logger.warning("Gemini returned empty text for invoice")
            return None

        # Strip markdown code fences if present
        text = text.strip()
        if text.startswith("```"):
            text = re.sub(r"^```\w*\n?", "", text)
            text = re.sub(r"\n?```$", "", text)
            text = text.strip()

        # Repair common LLM JSON issues (trailing commas, etc.)
        text = _repair_json(text)
        logger.info(f"Gemini invoice raw JSON ({len(text)} chars)")

        # If JSON is truncated, try to complete it
        if finish_reason == "MAX_TOKENS" or (text.count("{") > text.count("}")):
            logger.info("Detected truncated JSON, attempting to close")
            # Close any open strings, arrays, objects
            open_braces = text.count("{") - text.count("}")
            open_brackets = text.count("[") - text.count("]")
            # If we're mid-string, close it
            in_string = False
            for i, c in enumerate(text):
                if c == '"' and (i == 0 or text[i-1] != '\\'):
                    in_string = not in_string
            if in_string:
                text += '"'
            # Add null for truncated value
            if text.rstrip().endswith(":"):
                text += " null"
            elif text.rstrip().endswith(","):
                text = text.rstrip()[:-1]  # remove trailing comma
            text += "]" * open_brackets
            text += "}" * open_braces
            text = _repair_json(text)
            logger.info(f"Repaired truncated JSON ({len(text)} chars)")

        try:
            result = json_module.loads(text)
        except json_module.JSONDecodeError as parse_err:
            logger.warning(f"First JSON parse failed: {parse_err}")
            # Try extracting just the JSON object
            obj_match = re.search(r"\{[\s\S]*\}", text)
            if obj_match:
                try:
                    result = json_module.loads(_repair_json(obj_match.group()))
                except json_module.JSONDecodeError:
                    # Last resort: try to fix unescaped newlines in strings
                    fixed = re.sub(r'(?<=": ")(.*?)(?="[,}])', lambda m: m.group().replace('\n', ' '), text, flags=re.DOTALL)
                    result = json_module.loads(_repair_json(fixed))
            else:
                raise

        if not isinstance(result, dict):
            logger.warning(f"Gemini invoice returned non-dict: {type(result)}")
            return None

        # Convert amount to cents (integer) for normalize_invoice_data compatibility
        for field in ("amount", "subtotal", "tax"):
            if result.get(field) is not None:
                result[field] = int(float(result[field]) * 100)

        # Normalize line items — convert to cents (same as main totals)
        lines = result.get("lines") or []
        valid_lines = []
        for line in lines:
            # Filter out truncated/corrupt items from JSON repair
            if not line.get("description") and line.get("total") is None:
                logger.info(f"Dropping corrupt line item (no description or total): {line}")
                continue
            for field in ("unit_price", "tax", "total"):
                if line.get(field) is not None:
                    line[field] = int(round(float(line[field]) * 100))
            if line.get("quantity") is not None:
                line["quantity"] = float(line["quantity"])
            valid_lines.append(line)
        lines = valid_lines
        result["lines"] = lines

        logger.info(
            f"Gemini invoice extracted: issuer={result.get('issuer')}, "
            f"invoice_number={result.get('invoice_number')}, "
            f"total={result.get('amount')}, lines={len(lines)}"
        )

        return result

    except json_module.JSONDecodeError as e:
        logger.warning(f"Gemini invoice JSON parse error: {e}")
        return None
    except httpx.TimeoutException:
        logger.warning("Gemini invoice request timed out")
        return None
    except Exception as e:
        logger.warning(f"Gemini invoice extraction failed: {e}")
        return None
# CLAUDE-CHECKPOINT


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

        # Open image (auto-rotate based on EXIF for phone photos)
        original_image = _open_image_with_exif(contents)

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
            # Try template-based parsing first (if invoice2data is installed)
            if extract_data is not None:
                buffer = io.BytesIO(contents)
                data = extract_data(buffer, templates=TEMPLATES if TEMPLATES else None)
                if data:
                    normalized = normalize_invoice_data(data)
                    return JSONResponse(content=normalized)

            # Fall back to Gemini Vision AI for PDF parsing
            gemini_raw = await _extract_invoice_with_gemini(contents, "application/pdf")
            if gemini_raw:
                normalized = normalize_invoice_data(gemini_raw)
                normalized["extraction_method"] = "gemini"
                return JSONResponse(content=normalized)

            raise HTTPException(
                status_code=422,
                detail="Could not extract invoice data from PDF",
            )

        if is_image:
            # Primary: try Gemini Vision for accurate extraction
            fname = (file.filename or "").lower()
            if fname.endswith(".png"):
                img_mime = "image/png"
            else:
                img_mime = "image/jpeg"

            gemini_raw = await _extract_invoice_with_gemini(contents, img_mime)
            if gemini_raw:
                normalized = normalize_invoice_data(gemini_raw)
                normalized["extraction_method"] = "gemini"
                return JSONResponse(content=normalized)

            # Fallback: Tesseract OCR + regex parser
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
            normalized["extraction_method"] = "tesseract"
            return JSONResponse(content=normalized)

        raise HTTPException(status_code=400, detail="Unsupported file type")
    except HTTPException:
        raise
    except Exception as exc:  # pragma: no cover - defensive
        raise HTTPException(status_code=400, detail=str(exc))


@app.post("/parse-bank-statement")
async def parse_bank_statement(file: UploadFile = File(...)) -> JSONResponse:
    """
    Parse a bank statement image using OCR with table extraction.

    Uses Tesseract image_to_data for word-level bounding boxes, then
    reconstructs the table structure to extract individual transactions.

    Returns structured JSON with bank name, statement date, and transactions.
    """
    logger = logging.getLogger(__name__)

    try:
        contents = await file.read()
        if not contents:
            raise HTTPException(status_code=400, detail="Empty file")

        if pytesseract is None or Image is None:
            raise HTTPException(
                status_code=500,
                detail="OCR not available (pytesseract/Pillow missing)",
            )

        # Preprocess image for better table extraction
        processed_image = _preprocess_for_table(contents)
        original_image = _open_image_with_exif(contents)
        width, height = original_image.size

        langs = os.getenv("OCR_LANGS", "mkd+eng+srp")
        logger.info(f"Bank statement OCR with languages: {langs}")

        # Step 1: Extract plain text for metadata (bank name, date, account)
        plain_text = pytesseract.image_to_string(
            original_image, lang=langs, config="--oem 3 --psm 3"
        )
        logger.info(f"Plain text extracted: {len(plain_text)} chars")
        logger.info(f"First 300 chars: {plain_text[:300]}")

        bank_code, bank_name = _detect_bank_from_text(plain_text)
        statement_date = _extract_statement_date(plain_text)
        account_number = _extract_account_number(plain_text)

        logger.info(
            f"Detected: bank={bank_code}, date={statement_date}, account={account_number}"
        )

        # Step 2: Primary extraction via Gemini Vision (if available)
        transactions: List[Dict[str, Any]] = []
        extraction_method = "none"
        avg_confidence = 0.0

        # Determine MIME type for Gemini
        fname = (file.filename or "").lower()
        if fname.endswith(".png"):
            mime_type = "image/png"
        elif fname.endswith(".pdf"):
            mime_type = "application/pdf"
        else:
            mime_type = "image/jpeg"

        gemini_txs = await _extract_with_gemini(contents, mime_type)
        if gemini_txs:
            transactions = gemini_txs
            extraction_method = "gemini"
            avg_confidence = 95.0  # Gemini Vision is high-confidence
            logger.info(
                f"Gemini Vision extracted {len(transactions)} transactions"
            )

        # Step 3: Fallback to Tesseract TSV extraction if Gemini unavailable/failed
        debug_tsv_lines: Dict[str, List[str]] = {}
        winning_key = ""

        if not transactions:
            logger.info("Gemini unavailable/failed, falling back to Tesseract")
            for psm, img_label, img in [
                (6, "original", original_image),
                (6, "preprocessed", processed_image),
                (4, "original", original_image),
                (3, "original", original_image),
            ]:
                if transactions:
                    break
                attempt_key = f"psm{psm}_{img_label}"
                logger.info(f"TSV attempt: {attempt_key}")
                tsv_data = pytesseract.image_to_data(
                    img, lang=langs, config=f"--oem 3 --psm {psm}",
                    output_type=pytesseract.Output.DICT,
                )
                txs, tsv_lines = _extract_table_from_tsv(
                    tsv_data, width, own_account=account_number
                )
                if tsv_lines:
                    debug_tsv_lines[attempt_key] = tsv_lines[:60]
                if txs:
                    txs = [
                        tx for tx in txs
                        if (
                            tx.get("debit", 0) > 0
                            or tx.get("credit", 0) > 0
                            or (
                                tx.get("counterparty_account")
                                and len(str(tx["counterparty_account"])) >= 15
                            )
                        )
                    ]
                if txs:
                    transactions = txs
                    extraction_method = f"tsv_{attempt_key}"
                    winning_key = attempt_key
                    all_confs = [
                        int(c) for c in tsv_data.get("conf", []) if int(c) > 0
                    ]
                    avg_confidence = (
                        sum(all_confs) / len(all_confs) if all_confs else 0
                    )

        if not transactions:
            # Final fallback: parse from plain text using regex patterns
            logger.info("All TSV extraction failed, falling back to plain-text")
            transactions = _extract_transactions_from_text(plain_text)
            extraction_method = "text_fallback" if transactions else "none"

        # Re-number transactions sequentially after filtering
        for i, tx in enumerate(transactions):
            tx["row_number"] = i + 1

        logger.info(
            f"Result: {len(transactions)} transactions via {extraction_method}, "
            f"confidence={avg_confidence:.1f}%"
        )

        response: Dict[str, Any] = {
            "success": True,
            "bank_code": bank_code,
            "bank_name": bank_name,
            "statement_date": statement_date,
            "account_number": account_number,
            "transactions": transactions,
            "transaction_count": len(transactions),
            "confidence": round(avg_confidence / 100, 2),
            "extraction_method": extraction_method,
            "raw_text": plain_text[:2000],
            "image_width": width,
            "image_height": height,
        }

        # Include TSV debug lines if Tesseract was used
        if debug_tsv_lines:
            key = winning_key if winning_key in debug_tsv_lines else next(iter(debug_tsv_lines))
            response["debug_tsv_lines"] = debug_tsv_lines[key]

        return JSONResponse(content=response)

    except HTTPException:
        raise
    except Exception as exc:
        logging.exception("Bank statement OCR failed")
        raise HTTPException(
            status_code=500, detail=f"Bank statement OCR error: {str(exc)}"
        )
# CLAUDE-CHECKPOINT
