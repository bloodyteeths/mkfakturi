<?php

namespace App\Space;

use Illuminate\Support\Facades\File;
use Throwable;

class ImageUtils
{
    /**
     * Convert a file path or URL into a Base64 encoded image source.
     * Falls back to the default Facturino logo if the target cannot be resolved.
     *
     * @return string|null
     */
    public static function toBase64Src($path)
    {
        $fallback = base_path('logo/facturino_logo.png');

        foreach ([$path, $fallback] as $candidate) {
            if (! $candidate) {
                continue;
            }

            $contents = self::readImage($candidate);

            if (! $contents) {
                continue;
            }

            $mimeType = self::detectMimeType($candidate, $contents);

            if (! $mimeType) {
                continue;
            }

            return sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));
        }

        return null;
    }

    protected static function readImage(string $path): ?string
    {
        try {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $contents = @file_get_contents($path);
            } elseif (File::exists($path)) {
                $contents = File::get($path);
            } else {
                $contents = null;
            }
        } catch (Throwable $exception) {
            $contents = null;
        }

        return $contents === false ? null : $contents;
    }

    protected static function detectMimeType(string $path, string $contents): ?string
    {
        try {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = $finfo ? finfo_buffer($finfo, $contents) : null;
                if ($finfo) {
                    finfo_close($finfo);
                }

                return $mimeType ?: 'image/png';
            }

            if (File::exists($path)) {
                return File::mimeType($path);
            }
        } catch (Throwable $exception) {
            // ignore and fallback
        }

        return null;
    }
}
