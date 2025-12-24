<?php

namespace App\Traits;

trait GeneratesMenuTrait
{
    public function generateMenu($key, $user)
    {
        // Cache menu generation per user and company to avoid repeated processing
        // Include user role in cache key for proper filtering
        $cacheKey = "menu:{$key}:user:{$user->id}:role:{$user->role}:company:".request()->header('company', 'default');

        return \Cache::remember($cacheKey, \App\Providers\CacheServiceProvider::CACHE_TTLS['MEDIUM'], function () use ($key, $user) {
            $new_items = [];

            $menu = \Menu::get($key);
            $items = $menu ? $menu->items->toArray() : [];

            // Check user roles
            $isPartner = $user->role === 'partner';
            $isSuperAdmin = $user->role === 'super admin';

            foreach ($items as $data) {
                // Super admin only menu items (infrastructure settings)
                $superAdminOnly = $data->data['super_admin_only'] ?? false;
                if ($superAdminOnly && !$isSuperAdmin) {
                    continue;
                }

                // Feature flag check - skip menu items if feature is disabled
                $featureFlag = $data->data['feature_flag'] ?? null;
                if ($featureFlag) {
                    $featureFlags = \App\Models\Setting::getFeatureFlags();
                    if (!($featureFlags[$featureFlag] ?? false)) {
                        continue;
                    }

                    // Tier requirement check for features with minimum_tier
                    $featureConfig = config("features.{$featureFlag}");
                    $minimumTier = $featureConfig['minimum_tier'] ?? null;

                    if ($minimumTier) {
                        $companyId = request()->header('company');
                        $company = \App\Models\Company::find($companyId);

                        if ($company) {
                            $usageLimitService = app(\App\Services\UsageLimitService::class);
                            $currentTier = $usageLimitService->getCompanyTier($company);
                            $hierarchy = config('subscriptions.plan_hierarchy', []);

                            $currentLevel = $hierarchy[$currentTier] ?? 0;
                            $requiredLevel = $hierarchy[$minimumTier] ?? 0;

                            // Skip menu if current tier is below minimum required tier
                            if ($currentLevel < $requiredLevel) {
                                continue;
                            }
                        }
                    }
                }

                // Partner-only menu items (group starts with 'partner.')
                $group = $data->data['group'] ?? '';
                if (is_string($group) && str_starts_with($group, 'partner.')) {
                    // partner.accounting.* visible to partners AND super admins
                    // partner.console.* visible to partners only
                    if ($group === 'partner.accounting') {
                        if (!$isPartner && !$isSuperAdmin) {
                            continue;
                        }
                    } else {
                        // Other partner groups (console, etc.) - partners only
                        if (!$isPartner) {
                            continue;
                        }
                    }
                }

                if ($user->checkAccess($data)) {
                    $new_items[] = [
                        'title' => $data->title,
                        'link' => $data->link->path['url'],
                        'icon' => $data->data['icon'],
                        'name' => $data->data['name'],
                        'group' => $data->data['group'],
                    ];
                }
            }

            return $new_items;
        });
    }
}
// LLM-CHECKPOINT
