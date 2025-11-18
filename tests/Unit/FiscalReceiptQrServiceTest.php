<?php

use App\Services\FiscalReceiptQrService;

it('parses Macedonian fiscal QR payload correctly', function () {
    $service = new FiscalReceiptQrService;

    $payload = 'MK|TIN=MK1234567;DATETIME=2025-11-16T10:30:00;TOTAL=1500;VAT=200;FID=FSC98765;TYPE=INVOICE';

    $result = $service->parsePayload($payload);

    expect($result)->toHaveKeys([
        'issuer_tax_id',
        'date_time',
        'total',
        'vat_total',
        'fiscal_id',
        'type',
    ]);

    expect($result['issuer_tax_id'])->toBe('MK1234567');
    expect($result['date_time'])->toBeInstanceOf(\Carbon\Carbon::class);
    expect($result['date_time']->toIso8601String())->toStartWith('2025-11-16T10:30:00');
    expect($result['total'])->toBe(1500.0);
    expect($result['vat_total'])->toBe(200.0);
    expect($result['fiscal_id'])->toBe('FSC98765');
    expect($result['type'])->toBe('invoice');
});

it('throws when required fiscal QR fields are missing', function () {
    $service = new FiscalReceiptQrService;

    $payload = 'MK|TIN=MK1234567;TOTAL=1500;VAT=200;FID=FSC98765;TYPE=INVOICE';

    $service->parsePayload($payload);
})->throws(RuntimeException::class, 'Missing required fiscal QR fields');
