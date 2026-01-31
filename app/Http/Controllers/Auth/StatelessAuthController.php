<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\StatelessAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

/**
 * Stateless authentication controller.
 * 
 * Handles Keycloak OAuth flow and manages auth cookies.
 * No server-side sessions are used.
 */
class StatelessAuthController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    /**
     * Show login page or redirect to Keycloak
     */
    public function login(Request $request)
    {
        // If already authenticated, redirect to home
        if ($this->authService->isAuthenticated($request)) {
            return redirect()->intended('/');
        }

        $redirectUri = url('/auth/callback');
        $state = bin2hex(random_bytes(16));
        
        // Store state in cookie for CSRF protection
        $stateCookie = cookie('oauth_state', $state, 10, '/', null, true, true, false, 'lax');

        return redirect($this->authService->getAuthorizationUrl($redirectUri, $state))
            ->withCookie($stateCookie);
    }

    /**
     * Handle OAuth callback from Keycloak
     */
    public function callback(Request $request)
    {
        // Verify state for CSRF protection
        $state = $request->cookie('oauth_state');
        if (!$state || $state !== $request->query('state')) {
            return redirect('/login')->with('error', 'Invalid state parameter');
        }

        // Handle error response from Keycloak
        if ($request->has('error')) {
            return redirect('/login')->with('error', $request->query('error_description', 'Authentication failed'));
        }

        $code = $request->query('code');
        if (!$code) {
            return redirect('/login')->with('error', 'No authorization code received');
        }

        try {
            $result = $this->authService->handleCallback($code, url('/auth/callback'));
            
            // Create response with auth cookies
            $response = redirect()->intended('/');
            
            foreach ($result['cookies'] as $cookie) {
                $response->withCookie($cookie);
            }

            // Clear the state cookie
            $response->withCookie(cookie()->forget('oauth_state'));

            return $response;
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Refresh the access token
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        
        if (!$refreshToken) {
            return response()->json([
                'error' => 'NoRefreshToken',
                'message' => 'No refresh token available',
            ], 401);
        }

        try {
            $result = $this->authService->refreshTokens($refreshToken);
            
            $response = response()->json([
                'success' => true,
                'expires_in' => $result['tokens']['expires_in'] ?? 300,
            ]);
            
            foreach ($result['cookies'] as $cookie) {
                $response->withCookie($cookie);
            }

            return $response;
        } catch (\Exception $e) {
            // Clear invalid cookies and require re-login
            $response = response()->json([
                'error' => 'RefreshFailed',
                'message' => 'Token refresh failed',
            ], 401);

            foreach ($this->authService->createLogoutCookies() as $cookie) {
                $response->withCookie($cookie);
            }

            return $response;
        }
    }

    /**
     * Logout - clear cookies and redirect to Keycloak logout
     */
    public function logout(Request $request)
    {
        $logoutCookies = $this->authService->createLogoutCookies();
        
        // Get Keycloak logout URL
        $logoutUrl = $this->authService->getLogoutUrl(url('/'));
        
        $response = redirect($logoutUrl);
        
        foreach ($logoutCookies as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }

    /**
     * Get current user info (for API)
     */
    public function me(Request $request)
    {
        if (!$this->authService->isAuthenticated($request)) {
            return response()->json(null);
        }

        $user = $this->authService->getUser($request);
        
        return response()->json([
            'id' => $this->authService->getUserId($request),
            'tenant_id' => $this->authService->getTenantId($request),
            'email' => $user->email ?? null,
            'name' => $user->name ?? null,
            'roles' => $this->authService->getRoles($request),
            'is_admin' => $this->authService->isAdmin($request),
            'is_vendor' => $this->authService->isVendor($request),
        ]);
    }
}
