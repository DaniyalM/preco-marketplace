<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { ProductGrid } from '@/components/marketplace';
import { EmptyState } from '@/components/common';
import { Button } from '@/components/ui';
import { useWishlistStore } from '@/stores/wishlist';
import { onMounted, computed } from 'vue';

const wishlistStore = useWishlistStore();

onMounted(() => {
    wishlistStore.fetchWishlist();
});

// Convert wishlist items to product format for ProductGrid
const products = computed(() => {
    return wishlistStore.items.map(item => ({
        id: item.product.id,
        name: item.product.name,
        slug: item.product.slug,
        primary_image_url: item.product.primary_image_url,
        base_price: item.variant?.price ?? item.product.base_price,
        compare_at_price: item.product.compare_at_price,
        average_rating: 0,
        review_count: 0,
        is_in_stock: item.product.is_in_stock,
    }));
});

const handleRemove = async (product: { id: number }) => {
    await wishlistStore.remove(product.id);
};
</script>

<template>
    <AppLayout title="Wishlist">
        <Head title="My Wishlist" />

        <div class="container mx-auto px-4 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">My Wishlist</h1>
                    <p class="mt-1 text-muted-foreground">
                        {{ wishlistStore.count }} items saved
                    </p>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="!wishlistStore.loading && wishlistStore.count === 0"
                icon="heart"
                title="Your wishlist is empty"
                description="Save items you love to your wishlist and find them easily later."
                action-label="Browse Products"
                action-href="/products"
            />

            <!-- Wishlist Products -->
            <ProductGrid
                v-else
                :products="products"
                :loading="wishlistStore.loading"
                :columns="4"
                :show-quick-add="true"
                @wishlist="handleRemove"
            />
        </div>
    </AppLayout>
</template>
