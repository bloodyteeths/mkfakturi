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

