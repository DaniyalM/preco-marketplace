/**
 * Central query key factory for Vue Query.
 * Use these keys for cache invalidation and consistent key structure.
 */
export const queryKeys = {
    all: ['api'] as const,

    products: {
        all: () => [...queryKeys.all, 'products'] as const,
        list: (params?: Record<string, unknown>) =>
            [...queryKeys.products.all(), 'list', params ?? {}] as const,
        infiniteList: (params?: Record<string, unknown>) =>
            [...queryKeys.products.all(), 'infinite', params ?? {}] as const,
        detail: (slug: string) =>
            [...queryKeys.products.all(), 'detail', slug] as const,
        featured: () => [...queryKeys.products.all(), 'featured'] as const,
    },

    categories: {
        all: () => [...queryKeys.all, 'categories'] as const,
        list: (params?: Record<string, unknown>) =>
            [...queryKeys.categories.all(), 'list', params ?? {}] as const,
        detail: (slug: string) =>
            [...queryKeys.categories.all(), 'detail', slug] as const,
        products: (slug: string, params?: Record<string, unknown>) =>
            [...queryKeys.categories.all(), 'products', slug, params ?? {}] as const,
    },

    vendors: {
        all: () => [...queryKeys.all, 'vendors'] as const,
        list: (params?: Record<string, unknown>) =>
            [...queryKeys.vendors.all(), 'list', params ?? {}] as const,
        detail: (slug: string) =>
            [...queryKeys.vendors.all(), 'detail', slug] as const,
        products: (slug: string, params?: Record<string, unknown>) =>
            [...queryKeys.vendors.all(), 'products', slug, params ?? {}] as const,
    },

    cart: () => [...queryKeys.all, 'cart'] as const,
    wishlist: () => [...queryKeys.all, 'wishlist'] as const,
    authMe: () => [...queryKeys.all, 'auth', 'me'] as const,

    vendor: {
        dashboard: () => [...queryKeys.all, 'vendor', 'dashboard'] as const,
    },
} as const;
