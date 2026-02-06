<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { ProductGrid } from '@/components/marketplace';
import { EmptyState } from '@/components/common';
import { useWishlistQuery, useRemoveFromWishlistMutation } from '@/composables/useWishlistApi';
import { useAuthStore } from '@/stores/auth';
import { computed } from 'vue';

const authStore = useAuthStore();
const { data: wishlistItems, isLoading: wishlistLoading } = useWishlistQuery({
    enabled: computed(() => authStore.isAuthenticated),
});
const removeMutation = useRemoveFromWishlistMutation();

const wishlistCount = computed(() => wishlistItems.value?.length ?? 0);

const products = computed(() => {
    const items = wishlistItems.value ?? [];
    return items.map((item) => ({
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
    await removeMutation.mutateAsync(product.id);
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
                        {{ wishlistCount }} items saved
                    </p>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="!wishlistLoading && wishlistCount === 0"
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
                :loading="wishlistLoading"
                :columns="4"
                :show-quick-add="true"
                @wishlist="handleRemove"
            />
        </div>
    </AppLayout>
</template>
