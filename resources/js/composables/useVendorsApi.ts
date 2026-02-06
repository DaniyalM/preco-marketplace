import { useQuery } from '@tanstack/vue-query';
import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import {
    fetchVendors,
    fetchVendorBySlug,
    fetchVendorProducts,
    type VendorProductsParams,
} from '@/api/vendors';
import { queryKeys } from '@/queries/keys';

export function useVendorsQuery(
    params?: MaybeRefOrGetter<{ search?: string } | undefined>
) {
    return useQuery({
        queryKey: computed(() => queryKeys.vendors.list(toValue(params) ?? {})),
        queryFn: () => fetchVendors(toValue(params)),
    });
}

export function useVendorQuery(
    slug: MaybeRefOrGetter<string | undefined | null>,
    options?: { enabled?: boolean }
) {
    return useQuery({
        queryKey: computed(() => queryKeys.vendors.detail(toValue(slug) ?? '')),
        queryFn: () => fetchVendorBySlug(toValue(slug)!),
        enabled: computed(
            () =>
                (toValue(slug) != null && toValue(slug) !== '') &&
                (options?.enabled !== false)
        ),
    });
}

export function useVendorProductsQuery(
    slug: MaybeRefOrGetter<string | undefined | null>,
    params?: MaybeRefOrGetter<VendorProductsParams | undefined>,
    options?: { enabled?: boolean }
) {
    return useQuery({
        queryKey: computed(() =>
            queryKeys.vendors.products(toValue(slug) ?? '', toValue(params) ?? {})
        ),
        queryFn: () => fetchVendorProducts(toValue(slug)!, toValue(params)),
        enabled: computed(
            () =>
                (toValue(slug) != null && toValue(slug) !== '') &&
                (options?.enabled !== false)
        ),
    });
}
