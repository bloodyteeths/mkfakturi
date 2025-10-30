<?php

namespace App\Models;

use App\Providers\CacheServiceProvider;
use App\Traits\CacheableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class CompanySetting extends Model
{
    use CacheableTrait;
    use HasFactory;

    protected $fillable = ['company_id', 'option', 'value'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeWhereCompany($query, $company_id)
    {
        $query->where('company_id', $company_id);
    }

    public static function setSettings($settings, $company_id)
    {
        foreach ($settings as $key => $value) {
            self::updateOrCreate(
                [
                    'option' => $key,
                    'company_id' => $company_id,
                ],
                [
                    'option' => $key,
                    'company_id' => $company_id,
                    'value' => $value,
                ]
            );
        }
        
        // Clear company cache after updating settings
        Cache::flushCompanyCache($company_id);
    }

    public static function getAllSettings($company_id)
    {
        return Cache::companyRemember(
            'all_settings',
            CacheServiceProvider::CACHE_TTLS['COMPANY_SETTINGS'],
            function () use ($company_id) {
                return static::whereCompany($company_id)->get()->mapWithKeys(function ($item) {
                    return [$item['option'] => $item['value']];
                });
            }
        );
    }

    public static function getSettings($settings, $company_id)
    {
        $cacheKey = 'settings:' . md5(implode(',', $settings));
        return Cache::companyRemember(
            $cacheKey,
            CacheServiceProvider::CACHE_TTLS['COMPANY_SETTINGS'],
            function () use ($settings, $company_id) {
                return static::whereIn('option', $settings)->whereCompany($company_id)
                    ->get()->mapWithKeys(function ($item) {
                        return [$item['option'] => $item['value']];
                    });
            }
        );
    }

    public static function getSetting($key, $company_id)
    {
        return Cache::companyRemember(
            "setting:{$key}",
            CacheServiceProvider::CACHE_TTLS['COMPANY_SETTINGS'],
            function () use ($key, $company_id) {
                $setting = static::whereOption($key)->whereCompany($company_id)->first();
                return $setting ? $setting->value : null;
            }
        );
    }
}
