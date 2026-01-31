<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Selectively enable/disable SSR for specific routes.
 * 
 * SSR should be enabled for:
 * - Public pages that need SEO (products, categories, vendors, home)
 * - Pages that benefit from faster initial load
 * 
 * SSR should be disabled for:
 * - Dashboard pages (authenticated, no SEO needed)
 * - Admin pages (no public access)
 * - Pages with heavy client-side interactivity
 */
class SelectiveSsr
{
    /**
     * Routes that should have SSR enabled for SEO.
     * Uses route name patterns.
     */
    protected array $ssrEnabledRoutes = [
        // Public pages - SEO critical
        'welcome',
        'home',
        'products.index',
        'products.show',
        'categories.index',
        'categories.show',
        'vendors.index',
        'vendors.show',
    ];

    /**
     * Routes that should explicitly have SSR disabled.
     * These take precedence over enabled routes.
     */
    protected array $ssrDisabledRoutes = [
        // Authenticated dashboards - no SEO needed
        'vendor.*',
        'admin.*',
        'profile.*',
        'cart.*',
        'checkout.*',
        'orders.*',
        'wishlist.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        
        if ($routeName) {
            $shouldDisableSsr = $this->matchesPattern($routeName, $this->ssrDisabledRoutes);
            $shouldEnableSsr = $this->matchesPattern($routeName, $this->ssrEnabledRoutes);
            
            // Disable SSR for excluded routes
            if ($shouldDisableSsr) {
                $this->disableSsr();
            }
            // SSR is enabled by default in config, so we only need to disable
        }

        return $next($request);
    }

    protected function matchesPattern(string $routeName, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern === $routeName) {
                return true;
            }
            
            // Support wildcard patterns like 'vendor.*'
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, $routeName)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    protected function disableSsr(): void
    {
        // Temporarily disable SSR for this request
        config(['inertia.ssr.enabled' => false]);
    }
}
