<?php

namespace App\Http\Middleware;

use App\Services\StatelessAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to require specific user roles.
 * 
 * Stateless - reads roles from JWT token (set by VerifyKeycloakToken).
 */
class RequireRole
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  One or more roles required (OR logic)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // First check if user is authenticated
        if (!$this->authService->isAuthenticated($request)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'Authentication required',
                ], 401);
            }
            return redirect()->route('login');
        }

        // Check if user has at least one of the required roles
        $hasRole = $this->authService->hasAnyRole($roles, $request);

        if (!$hasRole) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You do not have permission to access this resource.',
                    'required_roles' => $roles,
                ], 403);
            }

            // For Inertia, return a 403 page
            return inertia('Errors/Forbidden', [
                'message' => 'You do not have permission to access this resource.',
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
