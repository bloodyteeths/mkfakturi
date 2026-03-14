<?php

namespace App\Space;

use App\Events\UpdateFinished;
use App\Models\Setting;
use Artisan;
use File;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use ZipArchive;

// Implementation taken from Akaunting - https://github.com/akaunting/akaunting
class Updater
{
    use SiteApi;

    public static function checkForUpdate($installed_version, $updater_channel = 'stable')
    {
        $data = null;
        $url = sprintf('releases/update-check/%s?channel=%s', $installed_version, $updater_channel);

        $response = static::getRemote($url, ['timeout' => 100, 'track_redirects' => true]);

        $data = (object) ['success' => false, 'release' => null];
        if ($response && ($response->getStatusCode() == 200)) {
            $data = $response->getBody()->getContents();
            $data = json_decode($data);
        }

        if ($data->success && $data->release && property_exists($data->release, 'extensions')) {
            $extensions = [];
            foreach ($data->release->extensions as $extension) {
                $extensions[$extension] = phpversion($extension) !== false;
            }
            $extensions['php'.'('.$data->release->min_php_version.')'] = version_compare(phpversion(), $data->release->min_php_version, '>=');
            $data->release->extensions = $extensions;
        }

        return $data;
    }

    public static function download($new_version, $is_cmd = 0)
    {
        $data = null;
        $path = null;

        $url = 'releases/download/'.$new_version.'.zip';
        $response = static::getRemote($url, ['timeout' => 100, 'track_redirects' => true]);

        // Exception
        if ($response instanceof RequestException) {
            return [
                'success' => false,
                'error' => 'Download Exception',
                'data' => [
                    'path' => $path,
                ],
            ];
        }

        if ($response && ($response->getStatusCode() == 200)) {
            $data = $response->getBody()->getContents();
        }

        // Create temp directory
        $temp_dir = storage_path('app/temp-'.md5(mt_rand()));

        if (! File::isDirectory($temp_dir)) {
            File::makeDirectory($temp_dir);
        }

        $zip_file_path = $temp_dir.'/upload.zip';

        // Add content to the Zip file
        $uploaded = is_int(file_put_contents($zip_file_path, $data)) ? true : false;

        if (! $uploaded) {
            return false;
        }

        return $zip_file_path;
    }

    public static function unzip($zip_file_path)
    {
        if (! file_exists($zip_file_path)) {
            throw new \Exception('Zip file not found');
        }

        // Prevent path traversal - zip file must be within storage directory
        $realZipPath = realpath($zip_file_path);
        $storagePath = realpath(storage_path());
        if (!$realZipPath || !str_starts_with($realZipPath, $storagePath)) {
            throw new \Exception('Invalid zip file path');
        }
        // CLAUDE-CHECKPOINT

        $temp_extract_dir = storage_path('app/temp2-'.md5(mt_rand()));

        if (! File::isDirectory($temp_extract_dir)) {
            File::makeDirectory($temp_extract_dir);
        }
        // Unzip the file
        $zip = new ZipArchive;

        if ($zip->open($zip_file_path)) {
            $zip->extractTo($temp_extract_dir);
        }

        $zip->close();

        // Delete zip file
        File::delete($zip_file_path);

        return $temp_extract_dir;
    }

    public static function copyFiles($temp_extract_dir)
    {
        if (! File::copyDirectory($temp_extract_dir.'/InvoiceShelf', base_path())) {
            return false;
        }

        // Delete temp directory
        File::deleteDirectory($temp_extract_dir);

        return true;
    }

    public static function deleteFiles($json)
    {
        $files = json_decode($json);
        $basePath = realpath(base_path());

        foreach ($files as $file) {
            // Prevent path traversal
            $fullPath = realpath(base_path($file));
            if (!$fullPath || !str_starts_with($fullPath, $basePath)) {
                Log::warning('Attempted path traversal in deleteFiles', ['file' => $file]);
                continue;
            }
            // Don't allow deleting critical files
            $protected = ['.env', 'artisan', 'composer.json', 'composer.lock'];
            if (in_array(basename($fullPath), $protected)) {
                Log::warning('Attempted to delete protected file', ['file' => $file]);
                continue;
            }
            \File::delete($fullPath);
        }
        // CLAUDE-CHECKPOINT

        return true;
    }

    public static function migrateUpdate()
    {
        Artisan::call('migrate --force');

        return true;
    }

    public static function finishUpdate($installed, $version)
    {
        Setting::setSetting('version', $version);
        event(new UpdateFinished($installed, $version));

        return [
            'success' => true,
            'error' => false,
            'data' => [],
        ];
    }
}
