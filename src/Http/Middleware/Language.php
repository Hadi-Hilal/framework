<?php

namespace Loxi5\Framework\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $languages = app('languages');
        $default_language = $languages->where('is_default', true)->first();
        $active_language = $default_language;
        if ($language = $languages->where('code_with_dash', request()->segment(1))->first()) {
            $active_language = $language;
        } elseif (auth()->check() && $language = $languages->where('code_with_dash', auth()->user()->default_language)->first()) {
            $active_language = $language;
        } elseif ($language = $languages->where('code_with_dash', $request->cookie('default_language', $default_language->code_with_dash))->first()) {
            $active_language = $language;
        }

        app()->setLocale($active_language->code_with_dash);
        view()->share('default_language', $active_language);
        Inertia::share('languages', $languages);
        Inertia::share('defaultLanguage', $active_language);
        if (auth()->check() && auth()->user()->default_language != $active_language->code_with_dash) {
            auth()->user()->default_language = $active_language->code_with_dash;
            auth()->user()->save();
        }

        if ($request->cookie('default_language', $default_language->code_with_dash) != $active_language->code_with_dash) {
            Cookie::queue('default_language', $active_language->code_with_dash);
        }

        return $next($request);
    }
}
