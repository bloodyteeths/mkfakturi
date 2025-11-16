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
        curl \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Pre-download ZXing JAR for pyzxing (prevents runtime 404 errors)
# pyzxing will try to download this from GitHub at runtime if not present
RUN mkdir -p /root/.local/share/pyzxing && \
    curl -L https://github.com/zxing/zxing/releases/download/zxing-3.4.1/zxing-3.4.1.jar \
    -o /root/.local/share/pyzxing/zxing.jar

COPY . .

EXPOSE 8000

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
