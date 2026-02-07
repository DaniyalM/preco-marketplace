<script setup lang="ts">
import { cn } from '@/lib/utils';
import ProductCard, { type Product } from './ProductCard.vue';
import { EmptyState } from '@/components/common';
import type { HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {
    products: Product[];
    loading?: boolean;
    columns?: 2 | 3 | 4 | 5;
    showVendor?: boolean;
    showRating?: boolean;
    showQuickAdd?: boolean;
    /** Use compact ProductCard (smaller image, padding, text) */
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    columns: 4,
    showVendor: true,
    showRating: true,
    showQuickAdd: true,
    compact: true,
});

const emit = defineEmits<{
    quickAdd: [product: Product];
    wishlist: [product: Product];
}>();

const gridClasses = {
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
};
</script>

<template>
    <div :class="cn('relative', $props.class)">
        <!-- Loading Skeleton -->
        <div
            v-if="loading"
            :class="cn('grid', compact ? 'gap-3 sm:gap-4' : 'gap-5 sm:gap-6', gridClasses[columns])"
        >
            <div
                v-for="i in columns * 3"
                :key="i"
                :class="compact ? 'rounded-xl' : 'rounded-2xl'"
                class="overflow-hidden border border-border/50 bg-card"
            >
                <div
                    :class="[
                        'relative w-full overflow-hidden bg-muted/60',
                        compact ? 'aspect-square' : 'aspect-[3/4]',
                    ]"
                >
                    <div class="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_ease-in-out_infinite] bg-gradient-to-r from-transparent via-muted/50 to-transparent" />
                </div>
                <div :class="compact ? 'space-y-2 p-2.5' : 'space-y-3 p-4'">
                    <div class="h-3 w-1/4 rounded-full bg-muted" />
                    <div :class="compact ? 'h-3 w-full' : 'h-4 w-full'" class="rounded-md bg-muted" />
                    <div :class="compact ? 'h-3 w-2/3' : 'h-4 w-2/3'" class="rounded-md bg-muted" />
                    <div class="flex gap-2 pt-2">
                        <div :class="compact ? 'h-4 w-14' : 'h-5 w-16'" class="rounded-md bg-muted" />
                        <div :class="compact ? 'h-4 w-10' : 'h-5 w-12'" class="rounded-full bg-muted" />
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Empty State -->
        <EmptyState
            v-else-if="products.length === 0"
            icon="box"
            title="No products found"
            description="Try adjusting your filters or search query to find what you're looking for."
        />
        
        <!-- Product Grid -->
        <div
            v-else
            :class="cn('grid', compact ? 'gap-3 sm:gap-4' : 'gap-5 sm:gap-6', gridClasses[columns])"
        >
            <ProductCard
                v-for="product in products"
                :key="product.id"
                :product="product"
                :compact="compact"
                :show-vendor="showVendor"
                :show-rating="showRating"
                :show-quick-add="showQuickAdd"
                @quick-add="emit('quickAdd', $event)"
                @wishlist="emit('wishlist', $event)"
            />
        </div>
    </div>
</template>
