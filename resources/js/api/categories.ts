import { http } from './client';

export async function fetchCategories(params?: {
    search?: string;
    roots_only?: boolean;
}) {
    const res = await http.get<{ data: unknown[] }>('/api/public/categories', {
        params,
    });
    return res.data.data;
}

export async function fetchCategoryBySlug(slug: string) {
    const res = await http.get<{ data: unknown }>(
        `/api/public/categories/${slug}`
    );
    return res.data.data;
}

export interface CategoryProductsParams {
    sort?: string;
    order?: string;
}

export async function fetchCategoryProducts(
    slug: string,
    params?: CategoryProductsParams
) {
    const res = await http.get<{ data: unknown[] }>(
        `/api/public/categories/${slug}/products`,
        { params }
    );
    return res.data.data;
}
