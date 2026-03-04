"""
invoice2data microservice with smart OCR preprocessing
Version: 1.2.0 - Bank statement OCR with table extraction
"""
import io
import logging
import os
import re
from typing import Any, Dict, List, Optional, Tuple

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
    """Parse amount string handling European/US formats."""
    s = raw.strip()
    if not s or s in ("0", "0.00", "0,00"):
        return 0.0
    s = re.sub(r"[^\d,.\-]", "", s)
    last_comma = s.rfind(",")
    last_period = s.rfind(".")
    if last_comma != -1 and last_period != -1:
        if last_period > last_comma:
            s = s.replace(",", "")
        else:
            s = s.replace(".", "").replace(",", ".")
    elif last_comma != -1:
        after = s[last_comma + 1:]
        if len(after) <= 2:
            s = s.replace(",", ".")
        else:
            s = s.replace(",", "")
    try:
        return float(s)
    except ValueError:
        return 0.0


def _extract_table_from_tsv(tsv_data: Dict[str, List], image_width: int) -> List[Dict[str, Any]]:
    """
    Extract table rows from Tesseract TSV output (image_to_data).
    Groups words by line, then maps to columns using X-position heuristics.
    """
    logger = logging.getLogger(__name__)

    n_words = len(tsv_data.get("text", []))
    if n_words == 0:
        return []

    # Build word list with positions
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
            "line_num": int(tsv_data["line_num"][i]),
            "block_num": int(tsv_data["block_num"][i]),
            "par_num": int(tsv_data["par_num"][i]),
        })

    if not words:
        return []

    # Group words into visual lines by clustering Y coordinates
    # Words within 15px vertical distance are on the same line
    words.sort(key=lambda w: (w["top"], w["left"]))

    lines: List[List[Dict]] = []
    current_line: List[Dict] = [words[0]]
    current_y = words[0]["top"]

    for w in words[1:]:
        if abs(w["top"] - current_y) < 15:
            current_line.append(w)
        else:
            lines.append(sorted(current_line, key=lambda x: x["left"]))
            current_line = [w]
            current_y = w["top"]
    if current_line:
        lines.append(sorted(current_line, key=lambda x: x["left"]))

    logger.info(f"Detected {len(lines)} visual lines from OCR")

    # Reconstruct text for each line
    line_texts = []
    for line_words in lines:
        parts = []
        for w in line_words:
            parts.append(w["text"])
        line_texts.append(" ".join(parts))

    # Find table header row and transaction area
    header_idx = -1
    totals_idx = len(lines)

    for i, text in enumerate(line_texts):
        lower = text.lower()
        if "р.бр" in lower or "р. бр" in lower:
            header_idx = i
        if "вкупно" in lower and header_idx >= 0:
            totals_idx = i
            break

    if header_idx < 0:
        logger.warning("Could not find table header (Р.бр.) in OCR text")
        return []

    logger.info(f"Table header at line {header_idx}, totals at line {totals_idx}")

    # Determine column boundaries from the header row
    # For Macedonian bank statements, typical columns (left to right):
    # row#, counterparty, account, method, debit, credit, fee, code, description, ref, complaint_ref
    #
    # Key approach: find the X positions of key header words to define column ranges
    header_words = lines[header_idx]

    # Build column X-ranges based on image width proportions
    # This is more robust than relying on exact header word positions
    # since OCR may merge/split header cells
    #
    # For Komercijalna format (from the sample image), approximate column positions as % of width:
    # row#: 0-4%, counterparty: 4-16%, account: 16-28%, method: 28-32%,
    # debit: 32-42%, credit: 42-52%, fee_debit: 52-56%, fee_credit: 56-60%,
    # code: 60-64%, description: 64-82%, pbo: 82-88%, ref: 88-100%

    # Detect column positions dynamically from header keywords
    col_positions = _detect_columns_from_header(header_words, image_width)
    logger.info(f"Detected column positions: {col_positions}")

    # Extract transaction rows (between header and totals)
    transactions = []

    # Skip sub-header rows (должи/побарува labels)
    start_idx = header_idx + 1
    for i in range(header_idx + 1, min(header_idx + 3, totals_idx)):
        text = line_texts[i].lower()
        if "должи" in text or "побарува" in text or "износ" in text:
            start_idx = i + 1

    for i in range(start_idx, totals_idx):
        line_word_list = lines[i]
        text = line_texts[i].strip()

        if not text or len(text) < 3:
            continue

        # Check if line starts with a number (transaction row indicator)
        first_word = line_word_list[0]["text"].strip()
        if not first_word.isdigit():
            # Could be a continuation of previous transaction description
            if transactions:
                prev = transactions[-1]
                prev["description"] = (prev.get("description", "") + " " + text).strip()
            continue

        tx = _map_words_to_columns(line_word_list, col_positions, image_width)
        tx["row_number"] = int(first_word)
        transactions.append(tx)

    logger.info(f"Extracted {len(transactions)} transactions from table")
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
        "counterparty_account": account if len(account) > 10 else None,
        "debit": debit,
        "credit": credit,
        "payment_code": payment_code,
        "description": description.strip() or None,
        "reference": reference.strip() or None,
        "pbo": pbo.strip() or None,
    }


def _extract_transactions_from_text(plain_text: str) -> List[Dict[str, Any]]:
    """
    Fallback: extract transactions from plain OCR text using regex patterns.

    Looks for lines containing account numbers (15-16 digit) near amounts
    (numbers with format like 10,000.00 or 6,000.00).

    This works when TSV-based table extraction fails (e.g., phone photos
    where word positions are unreliable).
    """
    logger = logging.getLogger(__name__)
    logger.info("Attempting plain-text transaction extraction (fallback)")

    transactions: List[Dict[str, Any]] = []

    # Macedonian bank account pattern: 15-16 consecutive digits,
    # or digits with a single space from OCR errors (e.g., "30000000467 7182")
    account_re = re.compile(r"\b(\d{15,16})\b|\b(\d{8,12}\s\d{3,6})\b")
    # Amount pattern: 10,000.00 or 6,000.00 or 12,100.00 or 0.00
    amount_re = re.compile(r"(\d{1,3}(?:,\d{3})*\.\d{2})")
    # Reference number (6-7 digit)
    ref_re = re.compile(r"\b(\d{6,7})\b")

    # Detect company's own account from header (Сметка XXXXX)
    own_account = None
    own_match = re.search(r"[Сс]метка\s*[:.]?\s*(\d[\d ]{12,})", plain_text)
    if own_match:
        own_account = re.sub(r"\s+", "", own_match.group(1))
        logger.info(f"Company's own account: {own_account}")

    lines = plain_text.splitlines()
    i = 0
    tx_num = 0

    while i < len(lines):
        line = lines[i].strip()
        if not line:
            i += 1
            continue

        # Look for lines with both account numbers AND amounts
        account_match = account_re.search(line)
        amounts_on_line = amount_re.findall(line)
        if not account_match or not amounts_on_line:
            i += 1
            continue

        # Extract account from whichever group matched
        raw_account = account_match.group(1) or account_match.group(2)
        account = re.sub(r"\s+", "", raw_account)

        # Skip if fewer than 15 digits after removing spaces
        if len(account) < 15:
            i += 1
            continue

        # Skip the company's own account (appears in header with balances)
        if own_account and account == own_account:
            i += 1
            continue

        # Gather context: this line + next few lines belong to one transaction
        context_lines = [line]
        j = i + 1
        while j < min(i + 8, len(lines)):
            next_line = lines[j].strip()
            if not next_line:
                j += 1
                continue
            # Stop if we hit another account number (next transaction)
            if account_re.search(next_line) and j > i + 1:
                break
            context_lines.append(next_line)
            j += 1

        context = " ".join(context_lines)

        # Extract amounts from context
        amounts = amount_re.findall(context)
        amounts_float = [_parse_amount(a) for a in amounts]
        amounts_float = [a for a in amounts_float if a > 0]

        if not amounts_float:
            i = j
            continue

        # Extract reference
        ref_match = ref_re.search(context)
        reference = ref_match.group(1) if ref_match else None

        # Extract counterparty name: text before the account number
        # Usually the company name appears before or around the account
        name_part = line[:account_match.start()].strip()
        # Also check previous non-empty line for name
        if not name_part and i > 0:
            for k in range(i - 1, max(i - 3, -1), -1):
                prev = lines[k].strip()
                if prev and not amount_re.search(prev) and len(prev) > 3:
                    name_part = prev
                    break

        # Clean up name - remove row numbers at start
        name_part = re.sub(r"^\d+\s+", "", name_part).strip()
        # Remove common noise
        name_part = re.sub(r"\|", "", name_part).strip()

        # Extract description from context (Macedonian keywords)
        description = ""
        desc_keywords = [
            "промет", "основ", "услуг", "плаќање", "СМЕТКОВОДСТВЕНА",
            "УСЛУГА", "извр", "произ", "стоки", "месец",
        ]
        for cl in context_lines[1:]:
            cl_clean = cl.strip()
            if any(kw.lower() in cl_clean.lower() for kw in desc_keywords):
                description = (description + " " + cl_clean).strip()

        if not description:
            # Use all context except account/amount lines as description
            for cl in context_lines[1:]:
                cl_clean = cl.strip()
                if cl_clean and not account_re.search(cl_clean):
                    description = (description + " " + cl_clean).strip()
                    break

        # Determine debit vs credit
        # For Komercijalna statements: first amount = debit, second = credit
        # If debit is 0.00 and credit > 0, it's incoming
        debit = 0.0
        credit = 0.0

        if len(amounts_float) >= 2:
            # Pattern: 0.00 followed by amount = credit; amount followed by 0.00 = debit
            if amounts_float[0] == 0 and amounts_float[1] > 0:
                credit = amounts_float[1]
            elif amounts_float[0] > 0:
                debit = amounts_float[0]
                if len(amounts_float) > 1 and amounts_float[1] > 0:
                    credit = amounts_float[1]
        elif len(amounts_float) == 1:
            # Single amount - check context for clues
            credit = amounts_float[0]

        # Extract payment code (3-digit near amounts)
        code_match = re.search(r"\b(\d{3})\b", context)
        payment_code = None
        if code_match:
            code_val = int(code_match.group(1))
            if 100 <= code_val <= 999:
                payment_code = code_val

        tx_num += 1
        tx = {
            "row_number": tx_num,
            "counterparty_name": name_part or None,
            "counterparty_account": account,
            "debit": debit,
            "credit": credit,
            "payment_code": payment_code,
            "description": description.strip() or None,
            "reference": reference,
            "pbo": None,
        }
        transactions.append(tx)
        logger.info(f"  TX {tx_num}: {name_part} | debit={debit} credit={credit} | {description[:50]}")

        i = j

    logger.info(f"Plain-text extraction found {len(transactions)} transactions")
    return transactions


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

        # Step 2: Extract word-level data with bounding boxes for table extraction
        # Use PSM 6 (uniform block) which works best for tabular data
        tsv_data = pytesseract.image_to_data(
            processed_image, lang=langs, config="--oem 3 --psm 6",
            output_type=pytesseract.Output.DICT,
        )

        # Step 3: Extract table structure from word positions
        transactions = _extract_table_from_tsv(tsv_data, width)

        if not transactions:
            # Fallback: try with original (unprocessed) image
            logger.info("No transactions from preprocessed image, trying original")
            tsv_data = pytesseract.image_to_data(
                original_image, lang=langs, config="--oem 3 --psm 6",
                output_type=pytesseract.Output.DICT,
            )
            transactions = _extract_table_from_tsv(tsv_data, width)

        if not transactions:
            # Second fallback: try PSM 3 (fully automatic)
            logger.info("Still no transactions, trying PSM 3")
            tsv_data = pytesseract.image_to_data(
                original_image, lang=langs, config="--oem 3 --psm 3",
                output_type=pytesseract.Output.DICT,
            )
            transactions = _extract_table_from_tsv(tsv_data, width)

        if not transactions:
            # Final fallback: parse from plain text using regex patterns
            logger.info("TSV extraction failed, falling back to plain-text parsing")
            transactions = _extract_transactions_from_text(plain_text)

        # Calculate average confidence
        all_confs = [
            int(c) for c in tsv_data.get("conf", []) if int(c) > 0
        ]
        avg_confidence = sum(all_confs) / len(all_confs) if all_confs else 0

        logger.info(
            f"Result: {len(transactions)} transactions, "
            f"confidence={avg_confidence:.1f}%"
        )

        return JSONResponse(content={
            "success": True,
            "bank_code": bank_code,
            "bank_name": bank_name,
            "statement_date": statement_date,
            "account_number": account_number,
            "transactions": transactions,
            "transaction_count": len(transactions),
            "confidence": round(avg_confidence / 100, 2),
            "raw_text": plain_text[:2000],
            "image_width": width,
            "image_height": height,
        })

    except HTTPException:
        raise
    except Exception as exc:
        logging.exception("Bank statement OCR failed")
        raise HTTPException(
            status_code=500, detail=f"Bank statement OCR error: {str(exc)}"
        )
# CLAUDE-CHECKPOINT
