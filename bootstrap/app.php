<?php

use App\Http\Middleware\CleanupMemory;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleWhiteLabeling;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\RequireVendorApproved;
use App\Http\Middleware\SelectiveSsr;
use App\Http\Middleware\SetTenantConnection;
use App\Http\Middleware\VerifyKeycloakToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Replace default EncryptCookies with our custom one that excludes JWT cookies
        $middleware->encryptCookies(except: [
            'access_token',
            'refresh_token',
            'id_token',
            'oauth_state',
            'oauth_code_verifier',
        ]);

        // Web routes - stateless with cookies for auth
        $middleware->web(append: [
            SelectiveSsr::class, // Selective SSR for SEO pages
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            HandleWhiteLabeling::class,
            VerifyKeycloakToken::class,
            SetTenantConnection::class,
            CleanupMemory::class, // Swoole memory cleanup
        ]);

        // API routes - fully stateless, no session
        $middleware->api(prepend: [
            VerifyKeycloakToken::class,
        ]);

        // API routes - append cleanup
        $middleware->api(append: [
            CleanupMemory::class, // Swoole memory cleanup
        ]);

        // Register middleware aliases for route protection
        $middleware->alias([
            'role' => RequireRole::class,
            'vendor.approved' => RequireVendorApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
