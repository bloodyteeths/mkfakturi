<?php

namespace Modules\Mk\Models;

use Illuminate\Database\Eloquent\Model;

class QrContactScan extends Model
{
    public $timestamps = false;

    protected $table = 'qr_contact_scans';

    protected $fillable = [
        'scanned_at',
        'ip_address',
        'user_agent',
        'device_type',
        'country',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    /**
     * Detect device type from User-Agent string.
     */
    public static function detectDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        $ua = strtolower($userAgent);

        if (preg_match('/iphone|ipod|android.*mobile|windows phone|blackberry/i', $ua)) {
            return 'mobile';
        }

        if (preg_match('/ipad|android(?!.*mobile)|tablet/i', $ua)) {
            return 'tablet';
        }

        return 'desktop';
    }
}

// CLAUDE-CHECKPOINT
