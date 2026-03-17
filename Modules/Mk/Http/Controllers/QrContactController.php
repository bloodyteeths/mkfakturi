<?php

namespace Modules\Mk\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Models\QrContactScan;

class QrContactController
{
    private const VCF_CONTENT = "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Facturino\r\nORG:Facturino\r\nTEL;TYPE=CELL:+38970253467\r\nEMAIL:info@facturino.mk\r\nURL:https://facturino.mk\r\nEND:VCARD";

    /**
     * Serve the VCF contact file and log the scan.
     */
    public function downloadVcf(Request $request): Response
    {
        // Fire-and-forget: log the scan without blocking the response
        try {
            $ip = $request->ip();
            $ua = $request->userAgent();

            QrContactScan::create([
                'scanned_at' => now(),
                'ip_address' => $ip,
                'user_agent' => $ua ? substr($ua, 0, 500) : null,
                'device_type' => QrContactScan::detectDeviceType($ua),
                'country' => self::countryFromIp($ip),
            ]);
        } catch (\Throwable $e) {
            // Never let analytics break the VCF download
            report($e);
        }

        return response(self::VCF_CONTENT, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'inline; filename="facturino-contact.vcf"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Return simple scan analytics as JSON.
     */
    public function stats(): JsonResponse
    {
        $now = now();

        $stats = DB::table('qr_contact_scans')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN DATE(scanned_at) = ? THEN 1 ELSE 0 END) as today', [$now->toDateString()])
            ->selectRaw('SUM(CASE WHEN scanned_at >= ? THEN 1 ELSE 0 END) as this_month', [$now->startOfMonth()->toDateTimeString()])
            ->first();

        $devices = DB::table('qr_contact_scans')
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        return response()->json([
            'total_scans' => (int) $stats->total,
            'scans_today' => (int) $stats->today,
            'scans_this_month' => (int) $stats->this_month,
            'device_breakdown' => $devices,
        ]);
    }

    /**
     * Best-effort country detection from IP using CloudFlare header
     * or Accept-Language as fallback. No external API calls.
     */
    private static function countryFromIp(?string $ip): ?string
    {
        // CloudFlare / Railway proxy headers (fastest, no external call)
        $country = request()->header('CF-IPCountry')
            ?? request()->header('X-Country-Code');

        if ($country && $country !== 'XX' && strlen($country) === 2) {
            return strtoupper($country);
        }

        // Accept-Language fallback: extract primary region
        $acceptLang = request()->header('Accept-Language', '');
        if (preg_match('/[a-z]{2}-([A-Z]{2})/', $acceptLang, $m)) {
            return $m[1];
        }

        return null;
    }
}

// CLAUDE-CHECKPOINT
