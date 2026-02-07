import { useQuery, useInfiniteQuery } from '@tanstack/vue-query';
import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import {
    fetchProducts,
    fetchProductsPage,
    fetchProductBySlug,
    fetchFeaturedProducts,
    type ProductListParams,
} from '@/api/products';
import { queryKeys } from '@/queries/keys';

export function useProductsInfiniteQuery(
    params?: MaybeRefOrGetter<ProductListParams | undefined>,
    options?: { perPage?: number }
) {
    const perPage = options?.perPage ?? 24;
    return useInfiniteQuery({
        queryKey: computed(() => queryKeys.products.infiniteList(toValue(params) ?? {})),
        queryFn: ({ pageParam }) =>
            fetchProductsPage({ ...toValue(params), per_page: perPage, page: pageParam }),
        initialPageParam: 1,
        getNextPageParam: (lastPage) => {
            const { current_page, last_page } = lastPage.meta;
            return current_page < last_page ? current_page + 1 : undefined;
        },
    });
}

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
