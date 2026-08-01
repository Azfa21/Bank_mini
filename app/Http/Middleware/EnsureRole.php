<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (in_array('nasabah', $roles) && Auth::guard('nasabah')->check()) {
            return $next($request);
        }

        if (Auth::guard('web')->check() && in_array(Auth::guard('web')->user()->role, $roles)) {
            return $next($request);
        }

        return redirect()->route('login');
    }
}
