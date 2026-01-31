import { defineStore } from 'pinia';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import type { User } from '@/types';

interface AuthState {
    initialized: boolean;
    refreshTimer: ReturnType<typeof setTimeout> | null;
}

/**
 * Stateless authentication store.
 * 
 * User data comes from Inertia's shared props (set by server middleware).
 * JWT is stored in httpOnly cookies and managed by the server.
 * This store handles token refresh and provides reactive auth state.
 */
export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        initialized: false,
        refreshTimer: null,
    }),

    getters: {
        /**
         * Get current user from Inertia shared props
         */
        user(): User | null {
            const page = usePage();
            return page.props?.auth?.user as User | null;
        },

        /**
         * Get user's roles
         */
        roles(): string[] {
            return this.user?.roles ?? [];
        },

        /**
         * Check if user is authenticated
         */
        isAuthenticated(): boolean {
            return this.user !== null;
        },

        /**
         * Check if user is admin
         */
        isAdmin(): boolean {
            return this.user?.is_admin ?? false;
        },

        /**
         * Check if user is vendor
         */
        isVendor(): boolean {
            return this.user?.is_vendor ?? false;
        },

        /**
         * Check if user is customer
         */
        isCustomer(): boolean {
            return this.user?.is_customer ?? false;
        },

        /**
         * Get vendor info if user is vendor
         */
        vendor() {
            const page = usePage();
            return page.props?.auth?.vendor ?? null;
        },

        /**
         * Get tenant ID
         */
        tenantId(): string | null {
            const page = usePage();
            return (page.props?.tenant as { id?: string })?.id ?? null;
        },
    },

    actions: {
        /**
         * Initialize auth (setup token refresh)
         */
        init(): void {
            if (this.initialized) return;
            
            this.initialized = true;
            
            // Setup periodic token refresh if authenticated
            if (this.isAuthenticated) {
                this.setupTokenRefresh();
            }
        },

        /**
         * Redirect to login
         */
        login(redirectUri?: string): void {
            const loginUrl = redirectUri 
                ? `/login?redirect=${encodeURIComponent(redirectUri)}`
                : '/login';
            window.location.href = loginUrl;
        },

        /**
         * Redirect to logout
         */
        logout(): void {
            // Clear refresh timer
            if (this.refreshTimer) {
                clearTimeout(this.refreshTimer);
                this.refreshTimer = null;
            }
            
            // Navigate to logout endpoint
            window.location.href = '/auth/logout';
        },

        /**
         * Register as new user (redirect to Keycloak registration)
         */
        register(): void {
            // Add registration action parameter for Keycloak
            window.location.href = '/login?kc_action=register';
        },

        /**
         * Refresh the access token
         */
        async refreshToken(): Promise<boolean> {
            try {
                const response = await axios.post('/auth/refresh');
                
                if (response.data.success) {
                    // Schedule next refresh
                    const expiresIn = response.data.expires_in ?? 300;
                    this.scheduleRefresh(expiresIn);
                    return true;
                }
                
                return false;
            } catch (error: any) {
                if (error.response?.status === 401) {
                    // Token refresh failed - user needs to re-login
                    console.warn('Token refresh failed, redirecting to login');
                    this.login(window.location.pathname);
                }
                return false;
            }
        },

        /**
         * Setup automatic token refresh
         */
        setupTokenRefresh(): void {
            // Refresh token 1 minute before expiry (assuming 5 min token)
            this.scheduleRefresh(240); // 4 minutes
        },

        /**
         * Schedule a token refresh
         */
        scheduleRefresh(seconds: number): void {
            if (this.refreshTimer) {
                clearTimeout(this.refreshTimer);
            }

            // Refresh slightly before expiry
            const refreshIn = Math.max((seconds - 60) * 1000, 30000); // At least 30s
            
            this.refreshTimer = setTimeout(() => {
                this.refreshToken();
            }, refreshIn);
        },

        /**
         * Check if user has a specific role
         */
        hasRole(role: string): boolean {
            return this.roles.includes(role);
        },

        /**
         * Check if user has any of the given roles
         */
        hasAnyRole(roles: string[]): boolean {
            return roles.some(role => this.hasRole(role));
        },

        /**
         * Check if user can access vendor features
         */
        canAccessVendorDashboard(): boolean {
            if (!this.isVendor) return false;
            return this.vendor?.is_approved ?? false;
        },
    },
});

// Configure axios to handle 401 responses globally
axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 401) {
            const authStore = useAuthStore();
            
            // Try to refresh the token
            if (error.response?.data?.error === 'TokenExpired') {
                const refreshed = await authStore.refreshToken();
                if (refreshed) {
                    // Retry the original request
                    return axios.request(error.config);
                }
            }
        }
        return Promise.reject(error);
    }
);
