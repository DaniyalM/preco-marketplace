<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Stateless authentication service for Keycloak integration.
 *
 * Handles token storage in cookies and provides helper methods
 * for accessing user context from the current request.
 */
class StatelessAuthService
{
    protected const ACCESS_TOKEN_COOKIE = 'access_token';
    protected const REFRESH_TOKEN_COOKIE = 'refresh_token';
    protected const ID_TOKEN_COOKIE = 'id_token';

    /**
     * Cookie options for secure token storage
     */
    protected array $cookieOptions;

    public function __construct()
    {
        // Use secure cookies only in production with HTTPS
        $isSecure = config('app.env') === 'production' && (
            config('session.secure', false) ||
            request()->secure()
        );

        // Handle null domain properly (null means use current domain)
        $domain = config('session.domain');
        if ($domain === 'null' || $domain === '') {
            $domain = null;
        }

        $this->cookieOptions = [
            'path' => '/',
            'domain' => $domain,
            'secure' => $isSecure,
            'httpOnly' => true,
            'sameSite' => 'lax',
        ];
    }

    /**
     * Get user ID from current request
     */
    public function getUserId(?Request $request = null): ?string
    {
        $request = $request ?? request();
        return $request->attributes->get('user_id');
    }

    /**
     * Get tenant ID from current request
     */
    public function getTenantId(?Request $request = null): ?string
    {
        $request = $request ?? request();
        return $request->attributes->get('tenant_id');
    }

    /**
     * Get user roles from current request
     */
    public function getRoles(?Request $request = null): array
    {
        $request = $request ?? request();
        return $request->attributes->get('keycloak_roles', []);
    }

    /**
     * Get full user object from current request
     */
    public function getUser(?Request $request = null): ?object
    {
        $request = $request ?? request();
        return $request->attributes->get('keycloak_user');
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(?Request $request = null): bool
    {
        return $this->getUserId($request) !== null;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role, ?Request $request = null): bool
    {
        return in_array($role, $this->getRoles($request));
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles, ?Request $request = null): bool
    {
        return !empty(array_intersect($roles, $this->getRoles($request)));
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(?Request $request = null): bool
    {
        return $this->hasRole('admin', $request);
    }

    /**
     * Check if user is vendor
     */
    public function isVendor(?Request $request = null): bool
    {
        return $this->hasRole('vendor', $request);
    }

    /**
     * Check if user is customer
     */
    public function isCustomer(?Request $request = null): bool
    {
        return $this->hasRole('customer', $request);
    }

    /**
     * Exchange authorization code for tokens and create auth cookies
     */
    public function handleCallback(string $code, string $redirectUri, ?string $codeVerifier = null): array
    {
        $tokenData = $this->exchangeCodeForTokens($code, $redirectUri, $codeVerifier);

        return [
            'tokens' => $tokenData,
            'cookies' => $this->createAuthCookies($tokenData),
        ];
    }

    /**
     * Exchange authorization code for tokens from Keycloak
     */
    protected function exchangeCodeForTokens(string $code, string $redirectUri, ?string $codeVerifier = null): array
    {
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.keycloak.client_id'),
            'client_secret' => config('services.keycloak.client_secret'),
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];

        // Add PKCE code verifier if provided
        if ($codeVerifier) {
            $params['code_verifier'] = $codeVerifier;
        }

        $response = Http::asForm()->post($this->getTokenEndpoint(), $params);

        if (!$response->successful()) {
            throw new \Exception('Failed to exchange code for tokens: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshTokens(string $refreshToken): array
    {
        $response = Http::asForm()->post($this->getTokenEndpoint(), [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.keycloak.client_id'),
            'client_secret' => config('services.keycloak.client_secret'),
            'refresh_token' => $refreshToken,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to refresh token');
        }

        return [
            'tokens' => $response->json(),
            'cookies' => $this->createAuthCookies($response->json()),
        ];
    }

    /**
     * Create auth cookies from token response
     */
    public function createAuthCookies(array $tokenData): array
    {
        $cookies = [];

        // Access token cookie
        if (isset($tokenData['access_token'])) {
            $cookies[] = $this->createCookie(
                self::ACCESS_TOKEN_COOKIE,
                $tokenData['access_token'],
                $tokenData['expires_in'] ?? 300
            );
        }

        // Refresh token cookie (longer lived)
        if (isset($tokenData['refresh_token'])) {
            $cookies[] = $this->createCookie(
                self::REFRESH_TOKEN_COOKIE,
                $tokenData['refresh_token'],
                $tokenData['refresh_expires_in'] ?? 1800
            );
        }

        // ID token cookie (for frontend use)
        if (isset($tokenData['id_token'])) {
            $cookies[] = $this->createCookie(
                self::ID_TOKEN_COOKIE,
                $tokenData['id_token'],
                $tokenData['expires_in'] ?? 300
            );
        }

        return $cookies;
    }

    /**
     * Create a secure httpOnly cookie
     */
    protected function createCookie(string $name, string $value, int $expiresIn): Cookie
    {
        return new Cookie(
            $name,
            $value,
            time() + $expiresIn,
            $this->cookieOptions['path'],
            $this->cookieOptions['domain'],
            $this->cookieOptions['secure'],
            $this->cookieOptions['httpOnly'],
            false, // raw
            $this->cookieOptions['sameSite']
        );
    }

    /**
     * Create logout cookies (expire all auth cookies)
     */
    public function createLogoutCookies(): array
    {
        return [
            $this->createCookie(self::ACCESS_TOKEN_COOKIE, '', -3600),
            $this->createCookie(self::REFRESH_TOKEN_COOKIE, '', -3600),
            $this->createCookie(self::ID_TOKEN_COOKIE, '', -3600),
        ];
    }

    /**
     * Get Keycloak token endpoint (uses internal URL for server-to-server calls)
     */
    protected function getTokenEndpoint(): string
    {
        // Use internal URL for server-to-server communication (works inside Docker network)
        $baseUrl = config('services.keycloak.internal_url') ?: config('services.keycloak.base_url');
        $realm = config('services.keycloak.realm');
        return "{$baseUrl}/realms/{$realm}/protocol/openid-connect/token";
    }

    /**
     * Generate PKCE code verifier
     */
    public function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Generate PKCE code challenge from verifier
     */
    public function generateCodeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * Get Keycloak authorization endpoint with PKCE
     */
    public function getAuthorizationUrl(string $redirectUri, ?string $state = null, ?string $codeChallenge = null, array $scopes = ['openid']): string
    {
        $baseUrl = config('services.keycloak.base_url');
        $realm = config('services.keycloak.realm');
        $clientId = config('services.keycloak.client_id');

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => $state ?? bin2hex(random_bytes(16)),
        ];

        // Add PKCE parameters if code challenge provided
        if ($codeChallenge) {
            $params['code_challenge'] = $codeChallenge;
            $params['code_challenge_method'] = 'S256';
        }

        return "{$baseUrl}/realms/{$realm}/protocol/openid-connect/auth?" . http_build_query($params);
    }

    /**
     * Get Keycloak logout URL
     */
    public function getLogoutUrl(string $redirectUri): string
    {
        $baseUrl = config('services.keycloak.base_url');
        $realm = config('services.keycloak.realm');
        $clientId = config('services.keycloak.client_id');

        $params = http_build_query([
            'client_id' => $clientId,
            'post_logout_redirect_uri' => $redirectUri,
        ]);

        return "{$baseUrl}/realms/{$realm}/protocol/openid-connect/logout?{$params}";
    }
}
