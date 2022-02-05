<?php

namespace Loxi5\Framework\Http\Middleware;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Menu\Models\MenuItem;
use Modules\Widget\Models\Widget;

class Frontend extends \Inertia\Middleware
{
    public function rootView(Request $request)
    {
        return 'Theme::frontend';
    }

    public function share(Request $request)
    {
        try {
            $menu_items = MenuItem::where('position', 'main')
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

            $footer_menu_items = MenuItem::where('position', 'footer')
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

            $account_menu_items = MenuItem::where('position', 'account')
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
        } catch (\Exception $e) {
            $menu_items = collect();

            $footer_menu_items = collect();

            $account_menu_items = collect();
        }

        return array_merge(parent::share($request), [
            'menuItems' => $menu_items,
            'footerMenuItems' => $footer_menu_items,
            'accountMenuItems' => $account_menu_items,
        ]);
    }
}
