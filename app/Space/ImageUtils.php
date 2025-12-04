<?php

namespace App\Space;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImageUtils
{
    // Maximum logo width for PDF embedding (in pixels)
    private const MAX_LOGO_WIDTH = 300;

    // Maximum file size before resizing (100KB)
    private const MAX_FILE_SIZE = 102400;

    /**
     * Convert a file path or URL into a Base64 encoded image source.
     * Falls back to the default Facturino logo if the target cannot be resolved.
     * Large images are automatically resized to prevent PDF generation issues.
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

            $originalSize = strlen($contents);

            // Resize if image is too large
            if ($originalSize > self::MAX_FILE_SIZE) {
                $resized = self::resizeImage($contents, $mimeType);
                if ($resized) {
                    $contents = $resized['contents'];
                    $mimeType = $resized['mime'];
                    Log::info('ImageUtils: Resized large image', [
                        'path' => substr($candidate, 0, 100),
                        'original_size' => $originalSize,
                        'new_size' => strlen($contents),
                    ]);
                }
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

    /**
     * Resize an image to a maximum width while preserving aspect ratio.
     * Converts to PNG for better quality.
     *
     * @return array|null ['contents' => string, 'mime' => string]
     */
    protected static function resizeImage(string $contents, string $mimeType): ?array
    {
        try {
            // Create image resource from string
            $image = @imagecreatefromstring($contents);
            if (! $image) {
                Log::warning('ImageUtils: Failed to create image from string');

                return null;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Calculate new dimensions
            if ($width <= self::MAX_LOGO_WIDTH) {
                // Image is already small enough, but we still compress it
                $newWidth = $width;
                $newHeight = $height;
            } else {
                $ratio = self::MAX_LOGO_WIDTH / $width;
                $newWidth = self::MAX_LOGO_WIDTH;
                $newHeight = (int) ($height * $ratio);
            }

            // Create new image with transparency support
            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);

            // Resize the image
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Output to buffer as PNG
            ob_start();
            imagepng($newImage, null, 6); // Quality level 6 (0-9, lower is less compression)
            $output = ob_get_clean();

            // Clean up
            imagedestroy($image);
            imagedestroy($newImage);

            return [
                'contents' => $output,
                'mime' => 'image/png',
            ];
        } catch (Throwable $e) {
            Log::warning('ImageUtils: Failed to resize image', ['error' => $e->getMessage()]);

            return null;
        }
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
