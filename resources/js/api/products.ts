import { http } from './client';

export interface ProductListParams {
    search?: string;
    sort?: string;
    order?: string;
    category?: string;
    per_page?: number;
    page?: number;
}

export interface ProductListMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export async function fetchProducts(params?: ProductListParams) {
    const res = await http.get<{ data: unknown[] }>('/api/public/products', {
        params,
    });
    return res.data.data;
}

export async function fetchProductsPage(params: ProductListParams & { page: number }) {
    const res = await http.get<{ data: unknown[]; meta: ProductListMeta }>('/api/public/products', {
        params: { ...params, page: params.page },
    });
    return { data: res.data.data, meta: res.data.meta };
}

export async function fetchFeaturedProducts() {
    const res = await http.get<{ data: unknown[] }>('/api/public/products/featured');
    return res.data.data;
}

export async function fetchProductBySlug(slug: string) {
    const res = await http.get<{ data: unknown }>(
        `/api/public/products/${slug}`
    );
    return res.data.data;
}
