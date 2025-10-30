<?php

namespace App\Traits;

use App\Providers\CacheServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait CacheableTrait
{
    /**
     * Boot the cacheable trait
     */
    public static function bootCacheableTrait(): void
    {
        // Clear cache when model is created, updated, or deleted
        static::created(function ($model) {
            $model->clearModelCache();
        });

        static::updated(function ($model) {
            $model->clearModelCache();
        });

        static::deleted(function ($model) {
            $model->clearModelCache();
        });
    }

    /**
     * Cache a query result
     */
    public function scopeCacheFor(Builder $query, int $seconds, string $key = null): Builder
    {
        $key = $key ?: $this->getCacheKey($query);
        
        return $query->remember($seconds, $key);
    }

    /**
     * Cache a query result with tags
     */
    public function scopeCacheWithTags(Builder $query, array $tags, int $seconds, string $key = null): Builder
    {
        $key = $key ?: $this->getCacheKey($query);
        
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggedCache) {
            return $query->remember($seconds, $key)->tags($tags);
        }
        
        return $query->remember($seconds, $key);
    }

    /**
     * Cache for a short period (5 minutes)
     */
    public function scopeCacheShort(Builder $query, string $key = null): Builder
    {
        return $this->scopeCacheFor($query, CacheServiceProvider::CACHE_TTLS['SHORT'], $key);
    }

    /**
     * Cache for a medium period (1 hour)
     */
    public function scopeCacheMedium(Builder $query, string $key = null): Builder
    {
        return $this->scopeCacheFor($query, CacheServiceProvider::CACHE_TTLS['MEDIUM'], $key);
    }

    /**
     * Cache for a long period (24 hours)
     */
    public function scopeCacheLong(Builder $query, string $key = null): Builder
    {
        return $this->scopeCacheFor($query, CacheServiceProvider::CACHE_TTLS['LONG'], $key);
    }

    /**
     * Cache for company scope
     */
    public function scopeCacheForCompany(Builder $query, int $seconds = null, string $key = null): Builder
    {
        $seconds = $seconds ?: CacheServiceProvider::CACHE_TTLS['MEDIUM'];
        $companyId = request()->header('company', 'default');
        $key = $key ?: $this->getCacheKey($query);
        $cacheKey = "company:{$companyId}:{$key}";
        
        return $query->remember($seconds, $cacheKey);
    }

    /**
     * Cache model attributes
     */
    public function cacheAttribute(string $attribute, $value = null, int $seconds = null): mixed
    {
        $seconds = $seconds ?: CacheServiceProvider::CACHE_TTLS['MEDIUM'];
        $cacheKey = $this->getModelCacheKey($attribute);
        
        if ($value !== null) {
            Cache::put($cacheKey, $value, $seconds);
            return $value;
        }
        
        return Cache::remember($cacheKey, $seconds, function () use ($attribute) {
            return $this->getAttribute($attribute);
        });
    }

    /**
     * Cache model relationship
     */
    public function cacheRelation(string $relation, int $seconds = null): mixed
    {
        $seconds = $seconds ?: CacheServiceProvider::CACHE_TTLS['MEDIUM'];
        $cacheKey = $this->getModelCacheKey("relation_{$relation}");
        
        return Cache::remember($cacheKey, $seconds, function () use ($relation) {
            return $this->getRelationValue($relation);
        });
    }

    /**
     * Cache computed values
     */
    public function cacheComputed(string $key, callable $callback, int $seconds = null): mixed
    {
        $seconds = $seconds ?: CacheServiceProvider::CACHE_TTLS['MEDIUM'];
        $cacheKey = $this->getModelCacheKey("computed_{$key}");
        
        return Cache::remember($cacheKey, $seconds, $callback);
    }

    /**
     * Get model-specific cache key
     */
    protected function getModelCacheKey(string $suffix = ''): string
    {
        $modelKey = static::class . ':' . ($this->getKey() ?? 'new');
        return $suffix ? "model:{$modelKey}:{$suffix}" : "model:{$modelKey}";
    }

    /**
     * Get cache key for query
     */
    protected function getCacheKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        return 'query:' . md5($sql . serialize($bindings));
    }

    /**
     * Clear all cache for this model instance
     */
    public function clearModelCache(): void
    {
        Cache::flushModelCache($this);
        
        // Also clear company cache if this model is company-scoped
        if (method_exists($this, 'company') || isset($this->company_id)) {
            $companyId = $this->company_id ?? request()->header('company');
            if ($companyId) {
                Cache::flushCompanyCache($companyId);
            }
        }
    }

    /**
     * Clear specific cached attribute
     */
    public function clearCachedAttribute(string $attribute): void
    {
        $cacheKey = $this->getModelCacheKey($attribute);
        Cache::forget($cacheKey);
    }

    /**
     * Clear specific cached relation
     */
    public function clearCachedRelation(string $relation): void
    {
        $cacheKey = $this->getModelCacheKey("relation_{$relation}");
        Cache::forget($cacheKey);
    }

    /**
     * Clear specific cached computed value
     */
    public function clearCachedComputed(string $key): void
    {
        $cacheKey = $this->getModelCacheKey("computed_{$key}");
        Cache::forget($cacheKey);
    }

    /**
     * Cache settings for this model
     */
    public function getCacheSettings(): array
    {
        return [
            'default_ttl' => CacheServiceProvider::CACHE_TTLS['MEDIUM'],
            'tags' => [$this->getTable()],
            'prefix' => strtolower(class_basename(static::class)),
        ];
    }

    /**
     * Check if caching is enabled for this model
     */
    public function isCachingEnabled(): bool
    {
        return config('cache.performance.stats_enabled', true) &&
               !app()->environment('testing');
    }

    /**
     * Get cache statistics for this model
     */
    public function getCacheStats(): array
    {
        if (!$this->isCachingEnabled()) {
            return [];
        }

        $prefix = $this->getModelCacheKey();
        
        // This would require cache store that supports pattern matching
        // For now, return basic info
        return [
            'cache_enabled' => true,
            'cache_prefix' => $prefix,
            'default_ttl' => $this->getCacheSettings()['default_ttl'],
        ];
    }
}