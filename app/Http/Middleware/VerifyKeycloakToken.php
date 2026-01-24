<?php
namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerifyKeycloakToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        $publicRoutes = [
            'api/public',
            'api/public/*',
            '/',
            'home',
            'login',
            'auth/callback'
        ];
        if($request->is($publicRoutes)){
            error_log('Public route accessed: '.$request->path());
            return $next($request);
        }

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            // 1. Fetch Keycloak Public Keys (Caches this in production!)
            $jwks = Cache::remember('keycloak_jwks', 86400, function () {
                return Http::get(env('KEYCLOAK_BASE_URL') . '/realms/' . env('KEYCLOAK_REALM') . '/protocol/openid-connect/certs')->json();
            });
            // 2. Decode and Validate
            $decoded = JWT::decode($token, JWK::parseKeySet($jwks));

            // 3. Optional: Attach user data to the request or log them in
            $request->attributes->add(['keycloak_user' => $decoded]);

            return $next($request);
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 401);
        }
    }
}
