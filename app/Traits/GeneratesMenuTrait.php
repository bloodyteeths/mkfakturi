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

            // Check if user is a partner
            $isPartner = $user->role === 'partner';

            foreach ($items as $data) {
                // Partner-only menu items (group starts with 'partner.')
                $group = $data->data['group'] ?? '';
                if (is_string($group) && str_starts_with($group, 'partner.')) {
                    // Only show partner menu items to partner users
                    if (!$isPartner) {
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
