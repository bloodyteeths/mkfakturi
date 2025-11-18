<?php

namespace App\Traits;

trait GeneratesMenuTrait
{
    public function generateMenu($key, $user)
    {
        // Cache menu generation per user and company to avoid repeated processing
        $cacheKey = "menu:{$key}:user:{$user->id}:company:".request()->header('company', 'default');

        return \Cache::remember($cacheKey, \App\Providers\CacheServiceProvider::CACHE_TTLS['MEDIUM'], function () use ($key, $user) {
            $new_items = [];

            $menu = \Menu::get($key);
            $items = $menu ? $menu->items->toArray() : [];

            // Cache isOwner check to avoid repeated DB queries
            $isOwner = $user->isOwner();

            foreach ($items as $data) {
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
