FROM python:3.11-slim

WORKDIR /app

ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1
# Default OCR languages: Macedonian + English + Serbian (Cyrillic support)
ENV OCR_LANGS=mkd+eng+srp

# Install system dependencies for OCR (Tesseract) with language packs
# and Java (for pyzxing DataMatrix decoder)
# Macedonian (mkd), English (eng), and Serbian (srp) for regional coverage
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        tesseract-ocr \
        tesseract-ocr-mkd \
        tesseract-ocr-eng \
        tesseract-ocr-srp \
        default-jre-headless \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 8000

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
