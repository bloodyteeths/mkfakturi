# Macedonian Module Services

This directory contains Macedonian-specific services for the Facturino application.

## Available Services

### 1. BarcodeService

Generates barcodes in various formats (CODE128, EAN13, UPC-A) for invoices and products.

**Package Required:** `picqer/php-barcode-generator`

**Installation:**
```bash
composer require picqer/php-barcode-generator
```

**Usage Examples:**

```php
use Modules\Mk\Services\BarcodeService;

// Get service instance
$barcodeService = app(BarcodeService::class);

// Generate CODE128 barcode for invoice (SVG format)
$svg = $barcodeService->generate('INV-2025-001', 'code128', 'svg');

// Generate EAN13 barcode (PNG format, base64-encoded)
$png = $barcodeService->generate('1234567890123', 'ean13', 'png');

// Generate invoice barcode (convenience method)
$barcode = $barcodeService->generateInvoiceBarcode('INV-2025-001');

// Generate product barcode (auto-detects type)
$barcode = $barcodeService->generateProductBarcode('1234567890123');

// Get barcode as data URI for HTML
$dataUri = $barcodeService->getDataUri('12345', 'code128', 'svg');
echo '<img src="' . $dataUri . '" alt="Barcode" />';

// Validate barcode
try {
    $isValid = $barcodeService->validate('1234567890123', 'ean13');
    echo "Barcode is valid!";
} catch (Exception $e) {
    echo "Invalid barcode: " . $e->getMessage();
}
```

**Supported Barcode Types:**

- **CODE128**: Alphanumeric barcode (up to 80 characters)
  - Use for: Invoice numbers, product codes, general purpose

- **EAN-13**: European Article Number (exactly 13 digits with check digit)
  - Use for: Retail products, international trade

- **UPC-A**: Universal Product Code (exactly 12 digits with check digit)
  - Use for: North American retail products

**Output Formats:**

- **SVG** (default): Scalable vector graphics, best for web and print
- **PNG**: Raster image, base64-encoded for direct use

---

### 2. QrCodeService

Generates QR codes for payments, invoices, and items with customizable sizes and error correction.

**Package Required:** `simplesoftwareio/simple-qr-code`

**Installation:**
```bash
composer require simplesoftwareio/simple-qr-code
```

**Usage Examples:**

```php
use Modules\Mk\Services\QrCodeService;

// Get service instance
$qrService = app(QrCodeService::class);

// Generate payment QR code (Macedonian format)
$paymentQr = $qrService->generatePaymentQr([
    'amount' => '1500.00',
    'currency' => 'MKD',
    'recipient' => 'Invoice Company LLC',
    'iban' => 'MK07200002785123453',
    'reference' => 'INV-2025-001',
    'purpose' => 'Payment for invoice INV-2025-001',
    'model' => '97' // Macedonian payment model
]);

// Generate item/product QR code
$itemQr = $qrService->generateItemQr([
    'sku' => 'PROD-12345',
    'name' => 'Premium Widget',
    'price' => '299.99',
    'url' => 'https://example.com/products/12345'
]);

// Generate invoice QR code with URL
$invoiceQr = $qrService->generateInvoiceQr([
    'invoice_number' => 'INV-2025-001',
    'amount' => '1500.00',
    'currency' => 'MKD',
    'date' => '2025-01-15',
    'url' => 'https://example.com/invoices/view/123'
]);

// Generate generic QR code
$qr = $qrService->generate(
    'https://example.com',
    'svg',      // format
    250,        // size
    'M'         // error correction
);

// Get QR code as data URI for HTML
$dataUri = $qrService->getDataUri('https://example.com', 'svg');
echo '<img src="' . $dataUri . '" alt="QR Code" />';

// Generate with custom size and error correction
$largeQr = $qrService->generatePaymentQr(
    $paymentData,
    'png',      // PNG format
    400,        // Large size
    'H'         // High error correction (best for payments)
);
```

**Error Correction Levels:**

- **L** (Low): 7% recovery - suitable for clean environments
- **M** (Medium): 15% recovery - balanced option (default)
- **Q** (Quartile): 25% recovery - good for moderate damage tolerance
- **H** (High): 30% recovery - best for harsh environments (recommended for payments)

**Recommended Sizes:**

- **150px** (Small): Email signatures, small printed materials
- **250px** (Medium): Invoices, receipts, standard documents (default)
- **400px** (Large): Posters, banners, large displays

**Output Formats:**

- **SVG** (default): Scalable vector graphics
- **PNG**: Raster image
- **EPS**: Encapsulated PostScript

---

## Controller Integration Example

```php
namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Models\Invoice;
use Modules\Mk\Services\BarcodeService;
use Modules\Mk\Services\QrCodeService;

class InvoiceExportController extends Controller
{
    public function generateInvoicePdf(Invoice $invoice)
    {
        $barcodeService = app(BarcodeService::class);
        $qrService = app(QrCodeService::class);

        // Generate barcode for invoice number
        $barcode = $barcodeService->generateInvoiceBarcode(
            $invoice->invoice_number,
            'svg'
        );

        // Generate payment QR code
        $paymentQr = $qrService->generatePaymentQr([
            'amount' => $invoice->total,
            'currency' => $invoice->currency->code,
            'recipient' => $invoice->company->name,
            'iban' => $invoice->company->iban,
            'reference' => $invoice->invoice_number,
            'purpose' => "Payment for invoice {$invoice->invoice_number}"
        ], 'svg', 250, 'H');

        return view('invoices.pdf', [
            'invoice' => $invoice,
            'barcode' => $barcode,
            'paymentQr' => $paymentQr
        ]);
    }
}
```

---

## Blade Template Integration

```blade
<!-- Invoice PDF Template -->
<div class="invoice-header">
    <h1>Invoice {{ $invoice->invoice_number }}</h1>

    <!-- Display barcode -->
    <div class="barcode">
        {!! $barcode !!}
    </div>
</div>

<div class="invoice-footer">
    <!-- Display payment QR code -->
    <div class="payment-qr">
        <h3>Scan to Pay</h3>
        <div class="qr-code">
            {!! $paymentQr !!}
        </div>
    </div>
</div>
```

---

## API Integration Example

```php
namespace App\Http\Controllers\V1\Admin\Item;

use App\Models\Item;
use Modules\Mk\Services\BarcodeService;
use Illuminate\Http\JsonResponse;

class ItemBarcodeController extends Controller
{
    public function generateBarcode(Item $item): JsonResponse
    {
        $barcodeService = app(BarcodeService::class);

        try {
            // Generate barcode based on item's barcode or SKU
            $code = $item->barcode ?? $item->sku;

            $barcode = $barcodeService->generateProductBarcode($code, 'svg');
            $dataUri = $barcodeService->getDataUri($code, 'code128', 'svg');

            return response()->json([
                'success' => true,
                'barcode_svg' => $barcode,
                'data_uri' => $dataUri
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
```

---

## Testing

Example unit test for services:

```php
namespace Tests\Unit\Services;

use Modules\Mk\Services\BarcodeService;
use Modules\Mk\Services\QrCodeService;
use Tests\TestCase;

class BarcodeServiceTest extends TestCase
{
    protected BarcodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BarcodeService::class);
    }

    public function test_generates_code128_barcode(): void
    {
        $barcode = $this->service->generate('TEST123', 'code128', 'svg');

        $this->assertStringContainsString('<svg', $barcode);
        $this->assertStringContainsString('</svg>', $barcode);
    }

    public function test_validates_ean13_barcode(): void
    {
        // Valid EAN13 with correct check digit
        $isValid = $this->service->validate('5901234123457', 'ean13');
        $this->assertTrue($isValid);
    }

    public function test_throws_exception_for_invalid_ean13(): void
    {
        $this->expectException(\Exception::class);
        $this->service->validate('123', 'ean13');
    }
}
```

---

## Configuration

No additional configuration required. Services are automatically registered via `MkServiceProvider`.

The services are registered as singletons in the Laravel container for optimal performance.

---

## Error Handling

Both services include comprehensive error handling and logging:

```php
try {
    $barcode = $barcodeService->generate($code, $type, $format);
} catch (\Exception $e) {
    // Error is logged automatically
    Log::error('Barcode generation failed', [
        'error' => $e->getMessage(),
        'code' => $code
    ]);

    // Handle the error
    return response()->json(['error' => 'Failed to generate barcode'], 500);
}
```

---

## Package Dependencies Summary

| Service | Package | Version | CLAUDE.md Status |
|---------|---------|---------|------------------|
| BarcodeService | picqer/php-barcode-generator | Latest | ✅ Whitelisted (INV-01) |
| QrCodeService | simplesoftwareio/simple-qr-code | Latest | ✅ Whitelisted (INV-01) |

**Installation Command:**
```bash
composer require picqer/php-barcode-generator simplesoftwareio/simple-qr-code
```

---

## Notes

- Both services follow PSR-12 coding standards
- All public methods include comprehensive PHPDoc documentation
- Services are registered as singletons for better performance
- Extensive validation ensures data integrity
- All operations are logged for debugging and auditing
- Services follow InvoiceShelf's existing patterns and conventions
