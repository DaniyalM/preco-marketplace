<?php

namespace App\Providers;

use App\Services\StatelessAuthService;
use App\Services\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TenantContext as singleton (request-scoped)
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });

        // Register StatelessAuthService as singleton
        $this->app->singleton(StatelessAuthService::class, function () {
            return new StatelessAuthService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
