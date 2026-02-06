import { useQuery } from '@tanstack/vue-query';
import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import {
    fetchCategories,
    fetchCategoryBySlug,
    fetchCategoryProducts,
    type CategoryProductsParams,
} from '@/api/categories';
import { queryKeys } from '@/queries/keys';

export function useCategoriesQuery(params?: {
    search?: string;
    roots_only?: boolean;
}) {
    return useQuery({
        queryKey: queryKeys.categories.list(params ?? {}),
        queryFn: () => fetchCategories(params),
    });
}

export function useCategoryQuery(
    slug: MaybeRefOrGetter<string | undefined | null>,
    options?: { enabled?: boolean }
) {
    return useQuery({
        queryKey: computed(() => queryKeys.categories.detail(toValue(slug) ?? '')),
        queryFn: () => fetchCategoryBySlug(toValue(slug)!),
        enabled: computed(
            () =>
                (toValue(slug) != null && toValue(slug) !== '') &&
                (options?.enabled !== false)
        ),
    });
}

export function useCategoryProductsQuery(
    slug: MaybeRefOrGetter<string | undefined | null>,
    params?: MaybeRefOrGetter<CategoryProductsParams | undefined>,
    options?: { enabled?: boolean }
) {
    return useQuery({
        queryKey: computed(() =>
            queryKeys.categories.products(toValue(slug) ?? '', toValue(params) ?? {})
        ),
        queryFn: () => fetchCategoryProducts(toValue(slug)!, toValue(params)),
        enabled: computed(
            () =>
                (toValue(slug) != null && toValue(slug) !== '') &&
                (options?.enabled !== false)
        ),
    });
}
