<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Card, CardContent, Badge, Button } from '@/components/ui';
import { Price, Rating } from '@/components/common';
import { Link } from '@inertiajs/vue3';
import { computed, type HTMLAttributes } from 'vue';

export interface Product {
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
    category?: {
        name: string;
        slug: string;
    };
}

interface Props extends /* @vue-ignore */ HTMLAttributes {
    product: Product;
    showVendor?: boolean;
    showRating?: boolean;
    showQuickAdd?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showVendor: true,
    showRating: true,
    showQuickAdd: true,
});

const emit = defineEmits<{
    quickAdd: [product: Product];
    wishlist: [product: Product];
}>();

const productUrl = computed(() => `/products/${props.product.slug}`);
const vendorUrl = computed(() => 
    props.product.vendor ? `/vendors/${props.product.vendor.slug}` : null
);
</script>

<template>
    <Card :class="cn('group overflow-hidden transition-shadow hover:shadow-lg', $props.class)">
        <!-- Image -->
        <Link :href="productUrl" class="relative block aspect-square overflow-hidden">
            <img
                v-if="product.primary_image_url"
                :src="product.primary_image_url"
                :alt="product.name"
                class="h-full w-full object-cover transition-transform group-hover:scale-105"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-muted"
            >
                <svg class="h-12 w-12 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            
            <!-- Badges -->
            <div class="absolute left-2 top-2 flex flex-col gap-1">
                <Badge v-if="product.is_featured" variant="default">Featured</Badge>
                <Badge v-if="!product.is_in_stock" variant="secondary">Out of Stock</Badge>
                <Badge
                    v-if="product.compare_at_price && product.compare_at_price > product.base_price"
                    variant="destructive"
                >
                    Sale
                </Badge>
            </div>
            
            <!-- Wishlist Button -->
            <button
                type="button"
                class="absolute right-2 top-2 rounded-full bg-white/80 p-2 opacity-0 shadow transition-opacity group-hover:opacity-100 hover:bg-white"
                @click.prevent="emit('wishlist', product)"
            >
                <svg class="h-5 w-5 text-muted-foreground hover:text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        </Link>
        
        <CardContent class="p-4">
            <!-- Vendor -->
            <Link
                v-if="showVendor && product.vendor && vendorUrl"
                :href="vendorUrl"
                class="mb-1 text-xs text-muted-foreground hover:text-primary"
            >
                {{ product.vendor.business_name }}
            </Link>
            
            <!-- Name -->
            <Link :href="productUrl">
                <h3 class="mb-2 line-clamp-2 font-medium transition-colors hover:text-primary">
                    {{ product.name }}
                </h3>
            </Link>
            
            <!-- Rating -->
            <Rating
                v-if="showRating"
                :value="product.average_rating"
                :review-count="product.review_count"
                size="sm"
                class="mb-2"
            />
            
            <!-- Price & Add to Cart -->
            <div class="flex items-center justify-between gap-2">
                <Price
                    :amount="product.base_price"
                    :compare-at="product.compare_at_price"
                />
                
                <Button
                    v-if="showQuickAdd && product.is_in_stock"
                    size="sm"
                    variant="outline"
                    @click="emit('quickAdd', product)"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
