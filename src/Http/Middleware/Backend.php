<?php

namespace Loxi5\Framework\Http\Middleware;

use Illuminate\Http\Request;
use Modules\Menu\Models\MenuItem;

class Backend extends \Inertia\Middleware
{
    public function rootView(Request $request)
    {
        return 'Theme::backend';
    }

    public function share(Request $request)
    {
        $menu_items = MenuItem::where('position', 'admin')
        ->with(['submenus' => function($query) {
            $query->where('is_active', 1)
                ->whereHas('module', function ($query) {
                    $query->where('is_active', 1);
                })
                ->orderBy('order');
        }])
        ->whereNull('parent_id')
        ->where('is_active', 1)
        ->whereHas('module', function ($query) {
            $query->where('is_active', 1);
        })
        ->orderBy('order')
        ->get();
        return array_merge(parent::share($request), [
            'menuItems' => $menu_items
        ]);
    }
}
