<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cookie;

class StoreLogoutUserRole
{
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            Cookie::queue('logout_role', auth()->user()->role, 10);
        }

        return $next($request);
    }
}
