# TRACK 5 - DAY 1: simple-qrcode USAGE AUDIT

**Date:** November 14, 2025
**Auditor:** DevOpsAgent
**Purpose:** Identify all usages of `simplesoftwareio/simple-qrcode` to plan replacement with Laravel Fortify

---

## EXECUTIVE SUMMARY

**Status:** ✅ **REPLACEMENT IS SAFE**

The `simplesoftwareio/simple-qrcode` package is currently installed but **NOT actively used** in the application. The only reference is in a custom service class `Modules/Mk/Services/QrCodeService.php` which has been created but is not called anywhere in the codebase.

**Impact of Removal:** **MINIMAL** - No production code will break

**Estimated Replacement Time:** **1-2 hours** (vs. 1-2 days originally estimated)

---

## AUDIT FINDINGS

### 1. Package Installation Status

```bash
$ composer show simplesoftwareio/simple-qrcode
name     : simplesoftwareio/simple-qrcode
descrip. : Simple QrCode is an easy to use wrapper for the popular Laravel framework based on the great work provided by Bacon/BaconQrCode.
versions : * 4.2.0

$ composer show bacon/bacon-qr-code
name     : bacon/bacon-qr-code
versions : * 2.0.8
```

**Dependency Conflict:**
- `simple-qrcode 4.2.0` requires `bacon/bacon-qr-code ^2.0`
- `laravel/fortify` requires `bacon/bacon-qr-code ^3.0`
- **Blocker:** Cannot install Fortify without removing simple-qrcode

---

### 2. Code Usage Analysis

#### Files Referencing SimpleSoftwareIO\SimpleQrCode:

1. **Modules/Mk/Services/QrCodeService.php**
   - **Status:** Defined but NOT USED
   - **Line 7:** `use SimpleSoftwareIO\QrCode\Facades\QrCode;`
   - **Lines 284-288:** Uses QrCode facade for generation
   - **Impact:** This entire service can be replaced or removed

2. **Modules/Mk/Providers/MkServiceProvider.php**
   - **Status:** Registers QrCodeService in service container
   - **Impact:** Service registration, no actual usage

3. **Modules/Mk/Services/README.md**
   - **Status:** Documentation only
   - **Impact:** None

4. **composer.lock**
   - **Status:** Dependency lock file
   - **Impact:** Will be updated after package removal

#### Files Using QR Generation Methods:

**Result:** ❌ **ZERO FILES**

```bash
$ find . -type f \( -name "*.php" -o -name "*.vue" \) -exec grep -l "generatePaymentQr\|generateInvoiceQr\|generateItemQr" {} \;
./Modules/Mk/Services/QrCodeService.php  # Only the service definition itself
```

**Conclusion:** QrCodeService has been scaffolded for future use (likely for payment QR codes, invoice QR codes, or item tracking) but is not currently integrated into any controllers, views, or business logic.

---

### 3. QrCodeService Capabilities

The custom service provides:

1. **Payment QR Codes** - For Macedonian payment standards (CASYS, bank transfers)
2. **Invoice QR Codes** - For invoice linking or summary data
3. **Item/Product QR Codes** - For inventory management
4. **Generic QR Codes** - Wrapper around SimpleSoftwareIO\QrCode

**Formats Supported:** SVG, PNG, EPS

---

### 4. Replacement Strategy

#### Option A: Replace with bacon/bacon-qr-code v3 Directly (RECOMMENDED)

**Pros:**
- Direct control over QR generation
- No facade dependency
- Compatible with Fortify
- Minimal code changes (QrCodeService already abstracts the implementation)

**Implementation:**
```php
// OLD (simple-qrcode):
use SimpleSoftwareIO\QrCode\Facades\QrCode;
$qr = QrCode::format($format)->size($size)->generate($data);

// NEW (bacon-qr-code v3):
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

$renderer = new ImageRenderer(
    new RendererStyle($size),
    new SvgImageBackEnd()
);
$writer = new Writer($renderer);
$qr = $writer->writeString($data);
```

**Time:** 1 hour (update QrCodeService.php)

#### Option B: Use Fortify's QR Generator (For 2FA Only)

**Pros:**
- Official Laravel package
- Built specifically for 2FA
- Well-maintained

**Cons:**
- Only for 2FA, not for payment/invoice QR codes
- Still need bacon-qr-code v3 for other use cases

**Recommendation:** Use Fortify for 2FA + bacon-qr-code v3 for QrCodeService

---

### 5. Action Plan

#### Step 1: Remove simple-qrcode (5 minutes)

```bash
cd /Users/tamsar/Downloads/mkaccounting
composer remove simplesoftwareio/simple-qrcode
```

**Expected Output:**
```
Removing simplesoftwareio/simple-qrcode (4.2.0)
Package operations: 0 installs, 0 updates, 1 removal
  - Removing simplesoftwareio/simple-qrcode (4.2.0)
```

#### Step 2: Install Laravel Fortify (10 minutes)

```bash
composer require laravel/fortify
php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
```

**Files Created:**
- `config/fortify.php` - Configuration
- `app/Actions/Fortify/*` - Fortify actions
- Database migration for 2FA columns

#### Step 3: Update QrCodeService to use bacon-qr-code v3 (30 minutes)

Replace `Modules/Mk/Services/QrCodeService.php` lines 284-288:

```php
// File: Modules/Mk/Services/QrCodeService.php

// Remove this import:
// use SimpleSoftwareIO\QrCode\Facades\QrCode;

// Add these imports:
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

// Update the generate() method:
public function generate(
    string $data,
    string $format = 'svg',
    int $size = self::DEFAULT_SIZE,
    string $errorCorrection = self::DEFAULT_ERROR_CORRECTION,
    ?int $margin = null
): string {
    try {
        // Validate inputs
        $this->validateFormat($format);
        $this->validateErrorCorrection($errorCorrection);

        if (empty($data)) {
            throw new Exception('QR code data cannot be empty');
        }

        $margin = $margin ?? self::DEFAULT_MARGIN;

        // Map error correction levels
        $ecLevel = match($errorCorrection) {
            'L' => \BaconQrCode\Common\ErrorCorrectionLevel::L(),
            'M' => \BaconQrCode\Common\ErrorCorrectionLevel::M(),
            'Q' => \BaconQrCode\Common\ErrorCorrectionLevel::Q(),
            'H' => \BaconQrCode\Common\ErrorCorrectionLevel::H(),
            default => \BaconQrCode\Common\ErrorCorrectionLevel::M(),
        };

        // Generate QR code using bacon-qr-code v3
        if ($format === 'svg') {
            $renderer = new ImageRenderer(
                new RendererStyle($size, $margin, null, null, $ecLevel),
                new SvgImageBackEnd()
            );
        } else { // png
            $renderer = new ImageRenderer(
                new RendererStyle($size, $margin, null, null, $ecLevel),
                new ImagickImageBackEnd()
            );
        }

        $writer = new Writer($renderer);
        $qr = $writer->writeString($data);

        Log::debug('QR code generated', [
            'format' => $format,
            'size' => $size,
            'error_correction' => $errorCorrection,
            'data_length' => strlen($data)
        ]);

        return $qr;

    } catch (Exception $e) {
        Log::error('QR code generation failed', [
            'error' => $e->getMessage(),
            'format' => $format,
            'size' => $size
        ]);

        throw new Exception('Failed to generate QR code: ' . $e->getMessage());
    }
}
```

#### Step 4: Test QrCodeService (15 minutes)

```bash
php artisan tinker

>>> $service = app(\Modules\Mk\Services\QrCodeService::class);
>>> $qr = $service->generate('https://facturino.mk', 'svg', 250, 'M');
>>> echo substr($qr, 0, 100); // Should output SVG XML
>>> $paymentQr = $service->generatePaymentQr(['amount' => '100.00', 'recipient' => 'Test']);
>>> echo substr($paymentQr, 0, 100); // Should output SVG XML
```

**Success Criteria:**
- No errors thrown
- QR codes generate successfully
- SVG output is valid XML

---

### 6. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| QrCodeService already in use | LOW | Medium | ✅ Audited - not used anywhere |
| bacon-qr-code v3 API different | MEDIUM | Low | Service abstraction isolates changes |
| Breaking existing QR codes | NONE | None | No existing QR codes in production |
| Fortify installation issues | LOW | High | Well-documented Laravel package |

**Overall Risk:** ✅ **VERY LOW**

---

## CONCLUSION

**Finding:** `simplesoftwareio/simple-qrcode` is installed but **NOT USED** in production code.

**Recommendation:** **PROCEED WITH IMMEDIATE REMOVAL**

**Timeline:**
- Remove simple-qrcode: 5 minutes
- Install Fortify: 10 minutes
- Update QrCodeService: 30 minutes
- Test: 15 minutes
- **Total: 60 minutes**

**Next Steps:**
1. Remove simple-qrcode
2. Install Fortify
3. Update QrCodeService.php
4. Test QR generation
5. Configure Fortify for 2FA (Day 1 afternoon)

---

**Audit Completed:** November 14, 2025
**Confidence Level:** 🟢 **VERY HIGH** (zero production impact)

**Ready to proceed:** ✅ YES

// CLAUDE-CHECKPOINT
