import { http } from './client';

export interface ProductListParams {
    search?: string;
    sort?: string;
    order?: string;
    category?: string;
    per_page?: number;
}

export async function fetchProducts(params?: ProductListParams) {
    const res = await http.get<{ data: unknown[] }>('/api/public/products', {
        params,
    });
    return res.data.data;
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
