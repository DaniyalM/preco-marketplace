<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Facades\Octane;

class OctaneServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 1. Reset specific singletons between requests
        Octane::prepareApplicationForNextOperation();

        // 2. Custom cleanup for your White-Label Service
        // This ensures the branding state is wiped before the next request starts
        Octane::prepareApplicationForNextRequest(function ($app) {
            if ($app->bound(\App\Services\BrandingService::class)) {
                $app->make(\App\Services\BrandingService::class)->forgetState();
            }
        });
    }
}
