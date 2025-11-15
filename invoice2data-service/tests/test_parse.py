import io

from fastapi.testclient import TestClient

from main import app

client = TestClient(app)


def test_health_endpoint():
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json()["status"] == "ok"


def test_parse_success(monkeypatch):
    from main import normalize_invoice_data

    def fake_extract_data(_buffer, templates=None):  # noqa: ARG001
        return {
            "issuer": "ACME Ltd",
            "invoice_number": "INV-123",
            "date": "2025-11-15",
            "amount": 100,
            "tax": 18,
        }

    import main as main_module

    monkeypatch.setattr(main_module, "extract_data", fake_extract_data)

    pdf_bytes = b"%PDF-1.4 test"
    response = client.post(
        "/parse",
        files={"file": ("invoice.pdf", io.BytesIO(pdf_bytes), "application/pdf")},
    )

    assert response.status_code == 200
    data = response.json()
    assert data["supplier"]["name"] == "ACME Ltd"
    assert data["invoice"]["number"] == "INV-123"
    assert data["totals"]["total"] == 100


def test_parse_no_template_match(monkeypatch):
    def fake_extract_data(_buffer, templates=None):  # noqa: ARG001
        return None

    import main as main_module

    monkeypatch.setattr(main_module, "extract_data", fake_extract_data)

    pdf_bytes = b"%PDF-1.4 test"
    response = client.post(
        "/parse",
        files={"file": ("invoice.pdf", io.BytesIO(pdf_bytes), "application/pdf")},
    )

    assert response.status_code == 422


def test_parse_malformed_pdf(monkeypatch):
    def fake_extract_data(_buffer, templates=None):  # noqa: ARG001
        raise ValueError("Malformed PDF")

    import main as main_module

    monkeypatch.setattr(main_module, "extract_data", fake_extract_data)

    pdf_bytes = b"not a real pdf"
    response = client.post(
        "/parse",
        files={"file": ("invoice.pdf", io.BytesIO(pdf_bytes), "application/pdf")},
    )

    assert response.status_code == 400


def test_parse_text_to_raw_prefers_total_keywords():
    from main import _parse_text_to_raw, normalize_invoice_data

    ocr_text = """
    МАРКЕТ ДОБАР ДЕН
    Фискална сметка бр. 1234567890123
    Артикл 1  10 x 50,00  = 500,00
    Артикл 2   5 x 20,00  = 100,00
    ВКУПНО: 600,00
    """

    raw = _parse_text_to_raw(ocr_text)
    normalized = normalize_invoice_data(raw)

    assert normalized["totals"]["total"] == 60000


def test_parse_text_to_raw_ignores_unreasonable_huge_numbers():
    from main import _parse_text_to_raw, normalize_invoice_data

    ocr_text = """
    SOME SHOP
    Code: 9999999999999999
    TOTAL TO PAY  123,45
    """

    raw = _parse_text_to_raw(ocr_text)
    normalized = normalize_invoice_data(raw)

    assert normalized["totals"]["total"] == 12345
