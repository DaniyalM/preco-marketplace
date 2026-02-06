import { http } from './client';

export async function fetchVendors(params?: { search?: string }) {
    const res = await http.get<{ data: unknown[] }>('/api/public/vendors', {
        params,
    });
    return res.data.data;
}

export async function fetchVendorBySlug(slug: string) {
    const res = await http.get<{ data: unknown }>(
        `/api/public/vendors/${slug}`
    );
    return res.data.data;
}

export interface VendorProductsParams {
    sort?: string;
    order?: string;
    page?: number;
}

export async function fetchVendorProducts(
    slug: string,
    params?: VendorProductsParams
) {
    const res = await http.get<{ data: unknown[] }>(
        `/api/public/vendors/${slug}/products`,
        { params }
    );
    return res.data.data;
}
