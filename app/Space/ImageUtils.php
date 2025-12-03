<?php

namespace App\Space;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
                Log::warning('ImageUtils: Failed to read image', ['path' => $candidate]);
                continue;
            }

            $mimeType = self::detectMimeType($candidate, $contents);

            if (! $mimeType) {
                Log::warning('ImageUtils: Failed to detect MIME type', ['path' => $candidate]);
                continue;
            }

            Log::info('ImageUtils: Successfully loaded image', [
                'path' => substr($candidate, 0, 100),
                'size' => strlen($contents),
                'mime' => $mimeType,
            ]);

            return sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));
        }

        return null;
    }

    protected static function readImage(string $path): ?string
    {
        try {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'user_agent' => 'Facturino/1.0',
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                $contents = @file_get_contents($path, false, $context);
                if ($contents === false) {
                    Log::warning('ImageUtils: file_get_contents failed for URL', [
                        'url' => $path,
                        'error' => error_get_last()['message'] ?? 'Unknown error',
                    ]);
                }
            } elseif (File::exists($path)) {
                $contents = File::get($path);
            } else {
                Log::warning('ImageUtils: File does not exist', ['path' => $path]);
                $contents = null;
            }
        } catch (Throwable $exception) {
            Log::error('ImageUtils: Exception reading image', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
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
