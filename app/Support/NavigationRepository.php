<?php

namespace App\Support;

use App\Models\NavigationMenu;
use App\Models\NavigationMenuItem;

/** Menu navigasi publik dalam bentuk array sederhana (di-cache ber-versi). */
class NavigationRepository
{
    /** @return list<array{label:string,url:string,new_tab:bool,children:list<array>}> */
    public function menu(string $key): array
    {
        return CmsCache::remember("menu:{$key}", function () use ($key) {
            $menu = NavigationMenu::query()->where('key', $key)->first();
            if (! $menu) {
                return [];
            }

            $items = NavigationMenuItem::query()->where('navigation_menu_id', $menu->id)->where('is_visible', true)
                ->with(['page.publishedRevision', 'product', 'industrySolution'])->orderBy('sort_order')->orderBy('id')->get();

            $map = fn (NavigationMenuItem $item) => ($url = $item->resolvedUrl()) !== null
                ? ['id' => $item->id, 'label' => $item->label, 'url' => UrlGuard::sanitize($url), 'new_tab' => $item->open_in_new_tab, 'parent_id' => $item->parent_id]
                : null;

            $flat = $items->map($map)->filter()->values();

            return $flat->whereNull('parent_id')->map(fn ($root) => $root + [
                'children' => $flat->where('parent_id', $root['id'])->values()->all(),
            ])->values()->all();
        });
    }
}
