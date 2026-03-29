<?php

namespace App\Http\Middleware;

use App\Models\AllowedOrigin;
use Closure;

class WhitelistOriginMiddleware
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
        $origin = $request->header('Origin');
        $allowedOrigins = ['http://localhost:3000', 'http://localhost:3001', 'http://127.0.0.1:3000', 'http://127.0.0.1:3001'];

        // Handle OPTIONS request (preflight) - Already handled by Cors middleware but just in case
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $allowedAllOrigin = AllowedOrigin::where('origin_url', '*')->exists();
        if ($allowedAllOrigin || in_array($origin, $allowedOrigins)) {
            return $next($request);
        }

        // Check if the origin exists in the database
        $allowedDbOrigin = AllowedOrigin::where('origin_url', $origin)->exists();
        
        // Postman/Empty origin check
        if (!$origin || $origin === 'postman') {
            $allowedDbOrigin = AllowedOrigin::where('origin_url', 'postman')->exists();
        }

        if ($allowedDbOrigin) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Access denied. Your origin is not allowed.',
            'origin' => $origin,
        ], 403);
    }
}
