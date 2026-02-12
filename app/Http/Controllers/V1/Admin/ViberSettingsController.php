<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Super-admin only — manages global Viber Business API credentials.
 * Tenant-level opt-in is stored in company_settings via CompanyController.
 */
class ViberSettingsController extends Controller
{
    private const VIBER_KEYS = [
        'viber_platform_enabled',
        'viber_auth_token',
        'viber_sender_name',
        'viber_allow_invoice_sent',
        'viber_allow_payment_received',
        'viber_allow_overdue_reminder',
        'viber_overdue_days',
    ];

    /**
     * GET /api/v1/admin/viber/settings
     * Returns all global Viber platform settings.
     */
    public function getSettings()
    {
        $settings = Setting::getSettings(self::VIBER_KEYS);

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * POST /api/v1/admin/viber/settings
     * Save global Viber platform settings (super-admin only).
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'viber_platform_enabled' => 'required|in:YES,NO',
            'viber_auth_token' => 'nullable|string|max:500',
            'viber_sender_name' => 'nullable|string|max:28',
            'viber_allow_invoice_sent' => 'required|in:YES,NO',
            'viber_allow_payment_received' => 'required|in:YES,NO',
            'viber_allow_overdue_reminder' => 'required|in:YES,NO',
            'viber_overdue_days' => 'nullable|in:7,14,30',
        ]);

        Setting::setSettings($validated);

        return response()->json([
            'success' => true,
            'message' => 'Viber platform settings saved.',
        ]);
    }

    /**
     * POST /api/v1/admin/viber/test-connection
     * Test Viber API auth token.
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'auth_token' => 'required|string',
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Viber-Auth-Token' => $request->auth_token,
            ])->get('https://chatapi.viber.com/pa/get_account_info');

            $body = $response->json();

            if ($response->ok() && ($body['status'] ?? 1) === 0) {
                return response()->json([
                    'success' => true,
                    'account_name' => $body['name'] ?? 'Unknown',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $body['status_message'] ?? 'Invalid token',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Connection failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * GET /api/v1/viber/availability
     * Public check (any authenticated user) — is Viber enabled platform-wide?
     * Does NOT expose the auth token or any secrets.
     */
    public function checkAvailability()
    {
        $enabled = Setting::getSetting('viber_platform_enabled');

        return response()->json([
            'available' => $enabled === 'YES',
        ]);
    }
}
