<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use App\Services\StatelessAuthService;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Inertia middleware for stateless authentication.
 * 
 * Shares user context from the JWT token with the frontend
 * without relying on server-side sessions.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $userData = $this->getUserData($request);

        return [
            ...parent::share($request),
            
            'auth' => [
                'user' => $userData,
                'vendor' => $this->getVendorData($request, $userData),
            ],
            
            'tenant' => [
                'id' => $this->authService->getTenantId($request),
            ],
            
            // Use URL-based flash messages instead of session
            'flash' => $this->getFlashFromUrl($request),
            
            // App configuration
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
            ],
        ];
    }

    /**
     * Get user data from request attributes (set by VerifyKeycloakToken)
     */
    protected function getUserData(Request $request): ?array
    {
        if (!$this->authService->isAuthenticated($request)) {
            return null;
        }

        $user = $this->authService->getUser($request);
        $roles = $this->authService->getRoles($request);

        return [
            'id' => $this->authService->getUserId($request),
            'email' => $user->email ?? null,
            'name' => $user->name ?? null,
            'given_name' => $user->given_name ?? null,
            'family_name' => $user->family_name ?? null,
            'email_verified' => $user->email_verified ?? false,
            'roles' => $roles,
            'is_admin' => in_array('admin', $roles),
            'is_vendor' => in_array('vendor', $roles),
            'is_customer' => in_array('customer', $roles),
        ];
    }

    /**
     * Get vendor data if user is a vendor
     */
    protected function getVendorData(Request $request, ?array $userData): ?array
    {
        if (!$userData || !in_array('vendor', $userData['roles'] ?? [])) {
            return null;
        }

        // First check if vendor is already attached to request (from RequireVendorApproved)
        $vendor = $request->attributes->get('vendor');

        if (!$vendor) {
            // Look up vendor by keycloak_user_id
            $vendor = Vendor::where('keycloak_user_id', $userData['id'])->first();
        }

        if (!$vendor) {
            return null;
        }

        return [
            'id' => $vendor->id,
            'business_name' => $vendor->business_name,
            'slug' => $vendor->slug,
            'status' => $vendor->status,
            'is_approved' => $vendor->isApproved(),
            'kyc_status' => $vendor->kyc?->status ?? 'not_submitted',
        ];
    }

    /**
     * Get flash messages from URL parameters (stateless approach)
     * 
     * Instead of sessions, pass flash messages via URL or response headers
     */
    protected function getFlashFromUrl(Request $request): array
    {
        return [
            'success' => $request->query('flash_success'),
            'error' => $request->query('flash_error'),
            'warning' => $request->query('flash_warning'),
            'info' => $request->query('flash_info'),
        ];
    }
}
