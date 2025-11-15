<?php

use App\Services\FiscalReceiptQrService;

test('parses valid macedonian fiscal qr string', function () {
    $service = new FiscalReceiptQrService();

    $payload = 'MK|TIN=MK1234567;DATETIME=2025-11-15T10:30:00;TOTAL=1000;VAT=180;FID=ABC123;TYPE=INVOICE';

    $result = $service->parsePayload($payload);

    expect($result['issuer_tax_id'])->toBe('MK1234567');
    expect($result['date_time'])->toBe('2025-11-15T10:30:00');
    expect($result['total'])->toBe(1000);
    expect($result['vat_total'])->toBe(180);
    expect($result['fiscal_id'])->toBe('ABC123');
    expect($result['type'])->toBe('invoice');
});

test('throws on unsupported qr format', function () {
    $service = new FiscalReceiptQrService();

    $this->expectException(RuntimeException::class);

    $service->parsePayload('INVALID|DATA');
});

test('throws on missing required fields', function () {
    $service = new FiscalReceiptQrService();

    $this->expectException(RuntimeException::class);

    $service->parsePayload('MK|TIN=MK1234567;TOTAL=1000');
});

