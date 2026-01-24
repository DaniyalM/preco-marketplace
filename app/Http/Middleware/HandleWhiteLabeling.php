<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Octane\Facades\Octane;
use App\Services\BrandingService;
use Inertia\Inertia;

class HandleWhiteLabeling
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // 1. Try to get branding from fast Swoole Table
        $tenant = Octane::table('tenants')->get($host);

        // 2. Fallback to DB if not in memory
        if (!$tenant) {
            // Replace with your actual DB lookup: $tenant = Tenant::where('domain', $host)->first();
            $tenant = [
                'id' => 1,
                'theme_color' => '#3b82f6',
                'vendor_name' => 'Acme Store'
            ];

            Octane::table('tenants')->set($host, $tenant);
        }

        // 3. Store in the stateless BrandingService
        app(BrandingService::class)->setConfig($tenant);

        // 4. Share with Inertia
        Inertia::share('brand', $tenant);

        return $next($request);
    }
}
