<?php

namespace App\Models;

use App\Providers\CacheServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['option', 'value'];

    protected static function cacheVersion(): int
    {
        return Cache::get('settings:version', 1);
    }

    protected static function bumpCacheVersion(): void
    {
        $current = Cache::get('settings:version', 1);
        Cache::forever('settings:version', $current + 1);
    }

    public static function setSetting($key, $setting)
    {
        $old = self::whereOption($key)->first();

        if ($old) {
            $old->value = $setting;
            $old->save();

            self::bumpCacheVersion();

            return;
        }

        $set = new Setting;
        $set->option = $key;
        $set->value = $setting;
        $set->save();

        self::bumpCacheVersion();
    }

    public static function setSettings($settings)
    {
        foreach ($settings as $key => $value) {
            self::updateOrCreate(
                [
                    'option' => $key,
                ],
                [
                    'option' => $key,
                    'value' => $value,
                ]
            );
        }

        self::bumpCacheVersion();
    }

    public static function getSetting($key)
    {
        $version = self::cacheVersion();

        return Cache::remember(
            "setting:v{$version}:{$key}",
            CacheServiceProvider::CACHE_TTLS['MEDIUM'],
            function () use ($key) {
                $setting = static::whereOption($key)->first();

                return $setting?->value;
            }
        );
    }

    public static function getSettings($settings)
    {
        $version = self::cacheVersion();
        $cacheKey = 'settings_batch:v'.$version.':'.md5(implode(',', $settings));

        return Cache::remember(
            $cacheKey,
            CacheServiceProvider::CACHE_TTLS['MEDIUM'],
            function () use ($settings) {
                return static::whereIn('option', $settings)
                    ->get()->mapWithKeys(function ($item) {
                        return [$item['option'] => $item['value']];
                    });
            }
        );
    }

    /**
     * Get feature flags with database values taking priority over config.
     *
     * @return array<string, bool>
     */
    public static function getFeatureFlags(): array
    {
        $version = self::cacheVersion();

        return Cache::remember(
            "feature_flags:v{$version}",
            CacheServiceProvider::CACHE_TTLS['MEDIUM'],
            function () {
                $feature_flags = [];
                $features_config = config('features', []);

                foreach ($features_config as $key => $feature) {
                    // Check database value first
                    $dbKey = 'feature_flag.'.$key;
                    $setting = static::whereOption($dbKey)->first();
                    $dbValue = $setting?->value;

                    if ($dbValue !== null) {
                        $feature_flags[$key] = filter_var($dbValue, FILTER_VALIDATE_BOOLEAN);
                    } else {
                        $feature_flags[$key] = $feature['enabled'] ?? false;
                    }
                }

                // Stock module is always enabled
                $feature_flags['stock'] = true;

                return $feature_flags;
            }
        );
    }
}
