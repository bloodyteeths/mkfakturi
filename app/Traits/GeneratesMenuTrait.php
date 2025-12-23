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
                }

                // Partner-only menu items (group starts with 'partner.')
                $group = $data->data['group'] ?? '';
                if (is_string($group) && str_starts_with($group, 'partner.')) {
                    // Show partner menu items to partners AND super admins
                    if (!$isPartner && !$isSuperAdmin) {
                        continue;
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
// CLAUDE-CHECKPOINT
