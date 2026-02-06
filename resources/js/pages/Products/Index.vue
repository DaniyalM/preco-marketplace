<script setup lang="ts">
import { AppLayout } from '@/components/layouts';
import { ProductGrid } from '@/components/marketplace';
import { Button, Card, CardContent, Combobox, Input } from '@/components/ui';
import { Head } from '@inertiajs/vue3';
import { useProductsQuery } from '@/composables/useProductsApi';
import { useAddCartItemMutation } from '@/composables/useCartApi';
import { useToggleWishlistMutation } from '@/composables/useWishlistApi';
import { useToastStore } from '@/stores/toast';
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

const search = ref('');
const sort = ref('created_at');

const sortOptions = [
    { value: 'created_at', label: 'Newest' },
    { value: 'price', label: 'Price: Low to High' },
    { value: 'price_desc', label: 'Price: High to Low' },
    { value: 'popularity', label: 'Most Popular' },
    { value: 'rating', label: 'Top Rated' },
].map((o) => ({ value: o.value, label: o.label }));

const queryParams = computed(() => {
    const params: Record<string, string> = {};
    if (search.value) params.search = search.value;
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

const { data: products, isLoading: loading, refetch } = useProductsQuery(queryParams);
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

        <div class="container mx-auto px-4 py-8">
            <!-- Filters -->
            <Card class="mb-8">
                <CardContent class="p-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <div class="flex-1">
                            <Input v-model="search" placeholder="Search products..." @keyup.enter="refetch()" />
                        </div>
                        <Combobox v-model="sort" :options="sortOptions" placeholder="Sort by" class="w-48" :searchable="true" />
                        <Button @click="refetch()">Search</Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Products Grid -->
            <ProductGrid :products="(products ?? []) as Product[]" :loading="loading" :columns="4" @quick-add="handleQuickAdd" @wishlist="handleWishlist" />
        </div>
    </AppLayout>
</template>
