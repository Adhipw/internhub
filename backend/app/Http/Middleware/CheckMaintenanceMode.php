<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow health checks to pass to prevent Railway from killing the container
        if ($request->is('up') || $request->is('api/v1/health')) {
            return $next($request);
        }

        // Cache the feature flag for 1 minute to avoid DB hits on every single request
        $isMaintenance = Cache::remember('maintenance_mode_enabled', 60, function () {
            return FeatureFlag::where('key', 'maintenance_mode')->value('is_enabled') ?? false;
        });

        if ($isMaintenance) {
            // Allow super_admin to bypass
            // For API requests, we need to check the sanctum guard
            $guard = $request->is('api/*') ? 'sanctum' : 'web';
            /** @var \App\Models\User|null $user */
            $user = \Illuminate\Support\Facades\Auth::guard($guard)->user();
            
            if ($user && $user->role === 'super_admin') {
                return $next($request);
            }
            
            // Allow logging out so users aren't stuck if they were logged in
            // Allow login and CSRF routes so super_admins can log back in
            $bypassedRoutes = [
                'login',
                'api/*/login',
                'logout',
                'api/*/logout',
                'sanctum/csrf-cookie'
            ];

            foreach ($bypassedRoutes as $route) {
                if ($request->is($route)) {
                    return $next($request);
                }
            }

            // Abort with 503 Maintenance
            abort(503, 'Sedang Update Sistem');
        }

        return $next($request);
    }
}
