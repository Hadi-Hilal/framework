<?php

namespace Loxi5\Framework\Http\Middleware;

use Illuminate\Support\Facades\Gate;
use Closure;

class HasAdminAccess
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
        Gate::allowIf(fn ($user) => optional($user)->has_permission && _user_config('Core.can_access_admin_panel'));

        return $next($request);
    }
}
