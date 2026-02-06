<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { ProductGrid } from '@/components/marketplace';
import { useLocale } from '@/composables/useLocale';
import { useProductsQuery, useFeaturedProductsQuery } from '@/composables/useProductsApi';
import { useAddCartItemMutation } from '@/composables/useCartApi';
import { useToggleWishlistMutation } from '@/composables/useWishlistApi';
import { useToastStore } from '@/stores/toast';
import type { Product } from '@/components/marketplace/ProductCard.vue';

const { t } = useLocale();

const featuredQuery = useFeaturedProductsQuery();
const trendingQuery = useProductsQuery({
    sort: 'trending',
    order: 'desc',
    per_page: 8,
});
const bestSellingQuery = useProductsQuery({
    sort: 'popularity',
    order: 'desc',
    per_page: 8,
});
const newArrivalsQuery = useProductsQuery({
    sort: 'created_at',
    order: 'desc',
    per_page: 8,
});

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
    <AppLayout :title="t('home.title')">
        <Head :title="t('home.title')" />

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <h1 class="sr-only">{{ t('home.heroTitle') }}</h1>

            <!-- Featured -->
            <section class="mb-12">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">{{ t('home.featured') }}</h2>
                    <Link
                        href="/products"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        {{ $t('common.viewAll') }}
                    </Link>
                </div>
                <ProductGrid
                    :products="(featuredQuery.data.value ?? []) as Product[]"
                    :loading="featuredQuery.isLoading.value"
                    :columns="4"
                    @quick-add="handleQuickAdd"
                    @wishlist="handleWishlist"
                />
            </section>

            <!-- Trending Now -->
            <section class="mb-12">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">{{ t('home.trending') }}</h2>
                    <Link
                        href="/products?sort=trending&order=desc"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        {{ $t('common.viewAll') }}
                    </Link>
                </div>
                <ProductGrid
                    :products="(trendingQuery.data.value ?? []) as Product[]"
                    :loading="trendingQuery.isLoading.value"
                    :columns="4"
                    @quick-add="handleQuickAdd"
                    @wishlist="handleWishlist"
                />
            </section>

            <!-- Best Selling -->
            <section class="mb-12">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">{{ t('home.bestSelling') }}</h2>
                    <Link
                        href="/products?sort=popularity&order=desc"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        {{ $t('common.viewAll') }}
                    </Link>
                </div>
                <ProductGrid
                    :products="(bestSellingQuery.data.value ?? []) as Product[]"
                    :loading="bestSellingQuery.isLoading.value"
                    :columns="4"
                    @quick-add="handleQuickAdd"
                    @wishlist="handleWishlist"
                />
            </section>

            <!-- New Arrivals -->
            <section class="mb-12">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">{{ t('home.newArrivals') }}</h2>
                    <Link
                        href="/products"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        {{ $t('common.viewAll') }}
                    </Link>
                </div>
                <ProductGrid
                    :products="(newArrivalsQuery.data.value ?? []) as Product[]"
                    :loading="newArrivalsQuery.isLoading.value"
                    :columns="4"
                    @quick-add="handleQuickAdd"
                    @wishlist="handleWishlist"
                />
            </section>
        </div>
    </AppLayout>
</template>
