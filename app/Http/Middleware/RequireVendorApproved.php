<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use App\Services\StatelessAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to require an approved vendor account.
 * 
 * Stateless - reads user from JWT token (set by VerifyKeycloakToken).
 */
class RequireVendorApproved
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    /**
     * Handle an incoming request.
     * Ensures the vendor is approved before allowing access to vendor features.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->authService->isAuthenticated($request)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        $userId = $this->authService->getUserId($request);
        $vendor = Vendor::where('keycloak_user_id', $userId)->first();

        if (!$vendor) {
            // No vendor profile - redirect to onboarding
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Vendor profile required',
                    'redirect' => '/vendor/onboarding',
                ], 403);
            }
            return redirect()->route('vendor.onboarding');
        }

        if (!$vendor->isApproved()) {
            // Vendor exists but not approved
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Vendor not approved',
                    'status' => $vendor->status,
                    'redirect' => '/vendor/status',
                ], 403);
            }
            return redirect()->route('vendor.status');
        }

        // Attach vendor to request for easy access in controllers
        $request->attributes->add(['vendor' => $vendor]);

        return $next($request);
    }
}
