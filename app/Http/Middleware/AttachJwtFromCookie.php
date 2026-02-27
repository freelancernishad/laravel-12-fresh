<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AttachJwtFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = null;

        if ($request->is('user') || $request->is('user/*')) {
             $token = $request->cookie('user_token') ?? $_COOKIE['user_token'] ?? null;
        } elseif ($request->is('admin') || $request->is('admin/*')) {
             $token = $request->cookie('admin_token') ?? $_COOKIE['admin_token'] ?? null;
        }

        // Fallback or generic logic if needed
        if (!$token) {
             $token = $request->cookie('admin_token') ?? $_COOKIE['admin_token'] ?? $request->cookie('user_token') ?? $_COOKIE['user_token'] ?? null;
        }

        if ($token && !$request->bearerToken()) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
