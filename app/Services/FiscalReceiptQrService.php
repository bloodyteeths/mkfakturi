<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Zxing\QrReader;

class FiscalReceiptQrService
{
    /**
     * Decode QR code from an uploaded receipt file and normalize payload.
     *
     * @return array<string,mixed>
     */
    public function decodeAndNormalize(UploadedFile $file): array
    {
        // Ensure we have at least one supported image backend
        if (! extension_loaded('imagick') && ! extension_loaded('gd')) {
            Log::error('FiscalReceiptQrService::decodeAndNormalize - No image backend available', [
                'imagick_loaded' => extension_loaded('imagick'),
                'gd_loaded' => extension_loaded('gd'),
            ]);

            throw new RuntimeException('QR decoding requires Imagick or GD PHP extensions to be enabled on the server.');
        }

        $path = $file->getRealPath();

        if (! $path) {
            throw new RuntimeException('Unable to read uploaded file');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $imagePath = $path;
        $temporaryImagePath = null;

        if ($extension === 'pdf') {
            if (! class_exists(\Imagick::class)) {
                throw new RuntimeException('PDF QR decoding requires Imagick extension');
            }

            $imagick = new \Imagick();
            $imagick->readImage($path.'[0]');
            $imagick->setImageFormat('png');

            $temporaryImagePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('qr_', true).'.png';
            $imagick->writeImage($temporaryImagePath);
            $imagick->clear();
            $imagick->destroy();

            $imagePath = $temporaryImagePath;
        }

        $qr = new QrReader($imagePath);
        $text = $qr->text();

        if ($temporaryImagePath && file_exists($temporaryImagePath)) {
            @unlink($temporaryImagePath);
        }

        if (! $text) {
            throw new RuntimeException('QR code not detected in receipt');
        }

        return $this->parsePayload($text);
    }

    /**
     * Parse a Macedonian fiscal QR payload string into a normalized DTO.
     *
     * Expected format (example):
     * MK|TIN=MK1234567;DATETIME=2025-11-15T10:30:00;TOTAL=1000;VAT=180;FID=ABC123;TYPE=CASH
     *
     * @return array<string,mixed>
     */
    public function parsePayload(string $payload): array
    {
        if (! str_starts_with($payload, 'MK|')) {
            throw new RuntimeException('Unsupported fiscal QR format');
        }

        $body = substr($payload, 3);
        $segments = explode(';', $body);

        $data = [];
        foreach ($segments as $segment) {
            if (! str_contains($segment, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $segment, 2);
            $data[strtoupper(trim($key))] = trim($value);
        }

        if (! isset($data['TIN'], $data['TOTAL'], $data['DATETIME'])) {
            throw new RuntimeException('Missing required fiscal QR fields');
        }

        $total = (int) ($data['TOTAL'] ?? 0);
        $vat = (int) ($data['VAT'] ?? 0);

        $type = strtolower($data['TYPE'] ?? 'CASH');
        $documentType = $type === 'invoice' ? 'invoice' : 'cash';

        return [
            'issuer_tax_id' => $data['TIN'],
            'date_time' => $data['DATETIME'],
            'total' => $total,
            'vat_total' => $vat,
            'fiscal_id' => $data['FID'] ?? null,
            'type' => $documentType,
        ];
    }
}
