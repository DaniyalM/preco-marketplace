import { useQuery } from '@tanstack/vue-query';
import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import {
    fetchProducts,
    fetchProductBySlug,
    fetchFeaturedProducts,
    type ProductListParams,
} from '@/api/products';
import { queryKeys } from '@/queries/keys';

export function useProductsQuery(
    params?: MaybeRefOrGetter<ProductListParams | undefined>
) {
    return useQuery({
        queryKey: computed(() =>
            queryKeys.products.list(toValue(params) ?? {})
        ),
        queryFn: () => fetchProducts(toValue(params)),
    });
}

export function useFeaturedProductsQuery() {
    return useQuery({
        queryKey: queryKeys.products.featured(),
        queryFn: fetchFeaturedProducts,
    });
}

export function useProductQuery(
    slug: string | undefined | null,
    options?: { enabled?: boolean }
) {
    return useQuery({
        queryKey: queryKeys.products.detail(slug ?? ''),
        queryFn: () => fetchProductBySlug(slug!),
        enabled:
            (slug != null && slug !== '') && (options?.enabled !== false),
    });
}
