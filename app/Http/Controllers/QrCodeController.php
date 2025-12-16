<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mk\Services\QrCodeService;

/**
 * QR Code Generation Controller
 *
 * Provides a simple endpoint for generating QR codes from URLs or text data.
 * Used by invitation pages, invoices, and other features requiring QR codes.
 */
class QrCodeController extends Controller
{
    protected QrCodeService $qrService;

    public function __construct(QrCodeService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Generate QR code from URL parameter
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     *
     * @example GET /api/qr?data=https://example.com/signup?ref=abc123
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'data' => 'required|string|max:2048',
            'format' => 'nullable|in:svg,png',
            'size' => 'nullable|integer|min:100|max:1000',
        ]);

        $data = $validated['data'];
        $format = $validated['format'] ?? 'svg';
        $size = $validated['size'] ?? 250;

        try {
            $qrCode = $this->qrService->generate($data, $format, $size);

            // Determine content type based on format
            $contentType = $format === 'svg' ? 'image/svg+xml' : 'image/png';

            return response($qrCode)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate QR code',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

// CLAUDE-CHECKPOINT
