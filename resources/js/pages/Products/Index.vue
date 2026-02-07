<script setup lang="ts">
import { AppLayout } from '@/components/layouts';
import { ProductCard } from '@/components/marketplace';
import { EmptyState } from '@/components/common';
import { Button, Combobox, Input } from '@/components/ui';
import { Head } from '@inertiajs/vue3';
import { useProductsInfiniteQuery } from '@/composables/useProductsApi';
import { useAddCartItemMutation } from '@/composables/useCartApi';
import { useToggleWishlistMutation } from '@/composables/useWishlistApi';
import { useToastStore } from '@/stores/toast';
import { useIntersectionObserver, refDebounced } from '@vueuse/core';
import { ref, computed } from 'vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    primary_image_url?: string | null;
    base_price: number;
    compare_at_price?: number | null;
    average_rating: number;
    review_count: number;
    is_in_stock: boolean;
    is_featured?: boolean;
    vendor?: {
        business_name: string;
        slug: string;
    };
}

const PER_PAGE = 12;
const DEBOUNCE_MS = 400;

const search = ref('');
const searchDebounced = refDebounced(search, DEBOUNCE_MS);
const sort = ref('created_at');
const feedScrollRef = ref<HTMLElement | null>(null);
const sentinelRef = ref<HTMLElement | null>(null);

const sortOptions = [
    { value: 'created_at', label: 'Newest' },
    { value: 'price', label: 'Price: Low to High' },
    { value: 'price_desc', label: 'Price: High to Low' },
    { value: 'popularity', label: 'Most Popular' },
    { value: 'rating', label: 'Top Rated' },
].map((o) => ({ value: o.value, label: o.label }));

const queryParams = computed(() => {
    const params: Record<string, string> = {};
    if (searchDebounced.value) params.search = searchDebounced.value;
    if (sort.value) {
        if (sort.value === 'price_desc') {
            params.sort = 'price';
            params.order = 'desc';
        } else {
            params.sort = sort.value;
            params.order = sort.value === 'price' ? 'asc' : 'desc';
        }
    }
    return params;
});

const {
    data,
    isLoading: loading,
    isFetchingNextPage,
    hasNextPage,
    fetchNextPage,
    refetch,
} = useProductsInfiniteQuery(queryParams, { perPage: PER_PAGE });

const allProducts = computed(() => (data.value?.pages ?? []).flatMap((p) => p.data as Product[]));

useIntersectionObserver(
    sentinelRef,
    ([entry]) => {
        if (entry?.isIntersecting && hasNextPage.value && !isFetchingNextPage.value) {
            void fetchNextPage();
        }
    },
    { root: feedScrollRef, threshold: 0.1 },
);

const addToCartMutation = useAddCartItemMutation();
const toggleWishlistMutation = useToggleWishlistMutation();
const toast = useToastStore();

const handleQuickAdd = async (product: Product) => {
    try {
        await addToCartMutation.mutateAsync({ product_id: product.id, quantity: 1 });
        toast.success('Added to cart');
    } catch {
        toast.error('Failed to add to cart');
    }
};

const handleWishlist = async (product: Product) => {
    try {
        await toggleWishlistMutation.mutateAsync({ productId: product.id });
        toast.success('Wishlist updated');
    } catch {
        toast.error('Failed to update wishlist');
    }
};
</script>

<template>
    <AppLayout title="Products">
        <Head title="Shop All Products" />

        <div class="flex h-[calc(100vh-4rem)] bg-muted/30">
            <!-- Left sidebar (fixed, LinkedIn-style) -->
            <aside class="flex w-64 shrink-0 flex-col border-r border-border/60 bg-background">
                <div class="sticky top-16 space-y-4 p-4">
                    <p class="text-sm font-semibold text-foreground">Filters</p>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-muted-foreground">Search</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <Input
                                v-model="search"
                                placeholder="Search..."
                                class="h-9 pl-9 text-sm"
                                @keyup.enter="refetch()"
                            />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-muted-foreground">Sort</label>
                        <Combobox
                            v-model="sort"
                            :options="sortOptions"
                            placeholder="Newest"
                            class="w-full text-sm"
                            :searchable="true"
                        />
                    </div>
                    <Button class="w-full" size="sm" @click="refetch()">Apply</Button>
                    <div class="border-t border-border/60 pt-4">
                        <p class="text-xs font-medium text-muted-foreground">Categories</p>
                        <nav class="mt-2 space-y-1">
                            <a href="/categories" class="block rounded-lg px-3 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground">All categories</a>
                        </nav>
                    </div>
                </div>
            </aside>

            <!-- Center: two-column grid of compact product cards -->
            <main
                ref="feedScrollRef"
                class="min-w-0 flex-1 overflow-y-auto"
            >
                <div class="mx-auto max-w-3xl px-4 py-6">
                    <template v-if="loading">
                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            <div v-for="i in 6" :key="i" class="overflow-hidden rounded-xl border border-border/60 bg-card">
                                <div class="aspect-square animate-pulse bg-muted/60" />
                                <div class="space-y-2 p-2.5">
                                    <div class="h-3 w-2/3 animate-pulse rounded bg-muted" />
                                    <div class="h-3 w-full animate-pulse rounded bg-muted" />
                                    <div class="h-4 w-1/2 animate-pulse rounded bg-muted" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template v-else-if="!allProducts.length">
                        <div class="flex min-h-[40vh] items-center justify-center">
                            <EmptyState
                                icon="box"
                                title="No products found"
                                description="Try adjusting your filters or search query."
                            />
                        </div>
                    </template>

                    <template v-else>
                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            <ProductCard
                                v-for="product in allProducts"
                                :key="product.id"
                                :product="product"
                                compact
                                @quick-add="handleQuickAdd"
                                @wishlist="handleWishlist"
                            />
                        </div>
                    </template>

                    <div ref="sentinelRef" class="h-2 w-full" />

                    <div v-if="isFetchingNextPage" class="flex justify-center py-6">
                        <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                    </div>
                </div>
            </main>

            <!-- Right sidebar (fixed, LinkedIn-style) -->
            <aside class="hidden w-72 shrink-0 flex-col border-l border-border/60 bg-background lg:flex">
                <div class="sticky top-16 space-y-4 p-4">
                    <p class="text-sm font-semibold text-foreground">Suggested</p>
                    <div class="rounded-xl border border-border/60 bg-muted/30 p-4">
                        <p class="text-sm text-muted-foreground">Trending and personalized picks will appear here.</p>
                        <a href="/products?sort=popularity" class="mt-2 inline-block text-sm font-medium text-primary hover:underline">View popular</a>
                    </div>
                    <div class="rounded-xl border border-border/60 bg-muted/30 p-4">
                        <p class="text-sm font-semibold text-foreground">Deals</p>
                        <p class="mt-1 text-xs text-muted-foreground">Save on selected items.</p>
                        <a href="/products" class="mt-2 inline-block text-sm font-medium text-primary hover:underline">Shop deals</a>
                    </div>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>
