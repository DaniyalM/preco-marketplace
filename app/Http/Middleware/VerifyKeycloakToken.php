<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stateless JWT verification middleware.
 * 
 * Reads JWT from cookie (or Authorization header as fallback),
 * validates it against Keycloak's public keys, and extracts
 * user_id, tenant_id, and roles for each request.
 * 
 * No server-side session is maintained.
 */
class VerifyKeycloakToken
{
    /**
     * Cookie names for storing JWT tokens
     */
    protected const TOKEN_COOKIE = 'access_token';
    protected const REFRESH_COOKIE = 'refresh_token';
    protected const ID_TOKEN_COOKIE = 'id_token';

    /**
     * Public routes that don't require authentication
     */
    protected array $publicRoutes = [
        'api/public',
        'api/public/*',
        '/',
        'home',
        'login',
        'auth/callback',
        'auth/logout',
        'test-job',
        'products',
        'products/*',
        'categories',
        'categories/*',
        'vendors',
        'vendors/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Allow public routes without token validation
        if ($this->isPublicRoute($request)) {
            // Still try to extract user info if token exists (for optional auth)
            $this->tryExtractUserFromToken($request);
            return $next($request);
        }

        // Get token from cookie first, then fallback to Authorization header
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return $this->unauthenticatedResponse($request);
        }

        try {
            $decoded = $this->validateToken($token);
            
            // Extract and attach user context to the request
            $this->attachUserContext($request, $decoded);

            return $next($request);
        } catch (ExpiredException $e) {
            // Token expired - frontend should refresh
            return $this->tokenExpiredResponse($request);
        } catch (Exception $e) {
            return $this->invalidTokenResponse($request, $e->getMessage());
        }
    }

    /**
     * Check if the current route is public
     */
    protected function isPublicRoute(Request $request): bool
    {
        return $request->is($this->publicRoutes);
    }

    /**
     * Get JWT token from cookie or Authorization header
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Priority 1: Cookie (preferred for web)
        $token = $request->cookie(self::TOKEN_COOKIE);
        
        if ($token) {
            return $token;
        }

        // Priority 2: Authorization Bearer header (for API clients)
        return $request->bearerToken();
    }

    /**
     * Try to extract user info from token on public routes (optional auth)
     */
    protected function tryExtractUserFromToken(Request $request): void
    {
        $token = $this->getTokenFromRequest($request);
        
        if (!$token) {
            return;
        }

        try {
            $decoded = $this->validateToken($token);
            $this->attachUserContext($request, $decoded);
        } catch (Exception $e) {
            // Silently fail for public routes - user just won't be authenticated
        }
    }

    /**
     * Validate the JWT token against Keycloak's public keys
     */
    protected function validateToken(string $token): object
    {
        $jwks = Cache::remember('keycloak_jwks', 3600, function () {
            $baseUrl = config('services.keycloak.base_url');
            $realm = config('services.keycloak.realm');
            
            $response = Http::timeout(10)->get("{$baseUrl}/realms/{$realm}/protocol/openid-connect/certs");
            
            if (!$response->successful()) {
                throw new Exception('Failed to fetch Keycloak public keys');
            }
            
            return $response->json();
        });

        return JWT::decode($token, JWK::parseKeySet($jwks));
    }

    /**
     * Attach user context to the request for downstream use
     * 
     * This replaces session-based user storage with request-scoped data
     */
    protected function attachUserContext(Request $request, object $decoded): void
    {
        $roles = $this->extractRoles($decoded);
        $tenantId = $this->extractTenantId($decoded);
        $userId = $this->extractUserId($decoded);
        
        // If no user ID in access token, try to get it from id_token
        // (Keycloak may not include 'sub' in access tokens depending on config)
        $idTokenData = null;
        if (!$userId) {
            $idTokenData = $this->getIdTokenData($request);
            if ($idTokenData) {
                $userId = $idTokenData->sub ?? null;
            }
        }

        // Merge data from id_token for user profile info
        if (!$idTokenData) {
            $idTokenData = $this->getIdTokenData($request);
        }

        $request->attributes->add([
            // Full decoded token for advanced use cases
            'keycloak_token' => $decoded,
            
            // Extracted user information
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'keycloak_roles' => $roles,
            
            // Convenience user object matching old structure
            'keycloak_user' => (object) [
                'sub' => $userId,
                'tenant_id' => $tenantId,
                'email' => $idTokenData->email ?? $decoded->email ?? null,
                'email_verified' => $idTokenData->email_verified ?? $decoded->email_verified ?? false,
                'name' => $idTokenData->name ?? $decoded->name ?? $decoded->preferred_username ?? null,
                'given_name' => $idTokenData->given_name ?? $decoded->given_name ?? null,
                'family_name' => $idTokenData->family_name ?? $decoded->family_name ?? null,
                'preferred_username' => $idTokenData->preferred_username ?? $decoded->preferred_username ?? null,
                'roles' => $roles,
            ],
        ]);
    }

    /**
     * Get decoded id_token data for user profile information
     */
    protected function getIdTokenData(Request $request): ?object
    {
        $idToken = $request->cookie(self::ID_TOKEN_COOKIE);
        
        if (!$idToken) {
            return null;
        }

        try {
            return $this->validateToken($idToken);
        } catch (Exception $e) {
            // If id_token validation fails, return null
            return null;
        }
    }

    /**
     * Extract user ID from token (sub claim)
     */
    protected function extractUserId(object $decoded): ?string
    {
        return $decoded->sub ?? null;
    }

    /**
     * Extract tenant ID from token
     * 
     * The tenant_id can be stored in different places depending on your Keycloak setup:
     * - Custom claim: tenant_id or tenantId
     * - In the audience
     * - As part of realm
     */
    protected function extractTenantId(object $decoded): ?string
    {
        // Check for custom tenant_id claim (configure in Keycloak mapper)
        if (isset($decoded->tenant_id)) {
            return $decoded->tenant_id;
        }

        if (isset($decoded->tenantId)) {
            return $decoded->tenantId;
        }

        // Check in token attributes/claims
        if (isset($decoded->attributes->tenant_id)) {
            return is_array($decoded->attributes->tenant_id) 
                ? $decoded->attributes->tenant_id[0] 
                : $decoded->attributes->tenant_id;
        }

        // Fallback: extract from azp (authorized party / client_id)
        // Useful if you have tenant-specific clients
        if (isset($decoded->azp) && str_contains($decoded->azp, '-')) {
            $parts = explode('-', $decoded->azp);
            if (count($parts) >= 2) {
                return $parts[0]; // e.g., "tenant1-app" -> "tenant1"
            }
        }

        // Default tenant for single-tenant or fallback
        return config('app.default_tenant', 'default');
    }

    /**
     * Extract roles from the decoded token
     */
    protected function extractRoles(object $decoded): array
    {
        $roles = [];

        // Realm roles
        if (isset($decoded->realm_access->roles)) {
            $roles = array_merge($roles, (array) $decoded->realm_access->roles);
        }

        // Client-specific roles
        $clientId = config('services.keycloak.client_id');
        if ($clientId && isset($decoded->resource_access->{$clientId}->roles)) {
            $roles = array_merge($roles, (array) $decoded->resource_access->{$clientId}->roles);
        }

        return array_unique($roles);
    }

    /**
     * Response for unauthenticated requests
     */
    protected function unauthenticatedResponse(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Authentication required',
            ], 401);
        }

        // For web requests, redirect to login
        return redirect()->guest('/login');
    }

    /**
     * Response for expired tokens
     */
    protected function tokenExpiredResponse(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'TokenExpired',
                'message' => 'Token has expired, please refresh',
            ], 401);
        }

        return redirect()->guest('/login');
    }

    /**
     * Response for invalid tokens
     */
    protected function invalidTokenResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'InvalidToken',
                'message' => 'Token validation failed: ' . $message,
            ], 401);
        }

        return redirect()->guest('/login');
    }
}
