<?php

namespace App\Http\Middleware;

use App\Services\TenantConnectionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant (marketplace) from the request and sets the default
 * DB connection to the tenant's database for data isolation.
 * Skip when on Super Admin or platform routes.
 */
class SetTenantConnection
{
    public function __construct(
        protected TenantConnectionResolver $resolver
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('super-admin*') || $request->is('login') || $request->is('auth/*')) {
            return $next($request);
        }

        $this->resolver->resolveFromRequest($request);

        return $next($request);
    }
}
