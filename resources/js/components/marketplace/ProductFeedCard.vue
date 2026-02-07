<script setup lang="ts">
import { Price, Rating, PolystarAvatar } from '@/components/common';
import { Button } from '@/components/ui';
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
}

const props = withDefaults(defineProps<Props>(), {
    showVendor: true,
    showRating: true,
});

const emit = defineEmits<{
    quickAdd: [product: Product];
    wishlist: [product: Product];
}>();

const productUrl = computed(() => `/products/${props.product.slug}`);
const vendorUrl = computed(() => (props.product.vendor ? `/vendors/${props.product.vendor.slug}` : null));

const hasDiscount = computed(
    () =>
        props.product.compare_at_price != null &&
        props.product.compare_at_price > props.product.base_price,
);
const discountPercent = computed(() => {
    if (!hasDiscount.value || !props.product.compare_at_price) return 0;
    return Math.round(100 - (props.product.base_price / props.product.compare_at_price) * 100);
});

const likeCount = computed(() => (props.product.id % 5) + 2);
const commentCount = computed(() => props.product.review_count || (props.product.id % 3) + 1);

const feedComments = computed(() => {
    const names = ['Alex M.', 'Jordan K.', 'Sam R.', 'Casey L.', 'Riley T.'];
    const messages = [
        'Looks great, thinking of getting one!',
        "How's the quality in person?",
        'Love this, ordered last week.',
        'Worth the price imo.',
        'Same here, really happy with it.',
    ];
    const n = Math.min(3, (props.product.id % 3) + 2);
    return Array.from({ length: n }, (_, i) => ({
        name: names[(props.product.id + i) % names.length],
        text: messages[(props.product.id * 2 + i) % messages.length],
    }));
});
</script>

<template>
    <article
        class="flex flex-col overflow-hidden rounded-2xl border border-border/60 bg-card shadow-sm transition-shadow hover:shadow-md"
    >
        <!-- Post header -->
        <div class="flex items-center gap-3 border-b border-border/40 px-4 py-3">
            <Link
                v-if="showVendor && product.vendor && vendorUrl"
                :href="vendorUrl"
                class="flex min-w-0 flex-1 items-center gap-3 rounded-lg transition-opacity hover:opacity-90"
            >
                <PolystarAvatar :name="product.vendor.business_name" size="default" />
                <div class="min-w-0">
                    <p class="truncate font-semibold text-foreground">{{ product.vendor.business_name }}</p>
                    <p class="text-xs text-muted-foreground">Vendor</p>
                </div>
            </Link>
            <template v-else>
                <PolystarAvatar name="Store" size="default" />
                <p class="font-semibold text-foreground">Product</p>
            </template>
            <span class="shrink-0 text-xs text-muted-foreground">Just now</span>
        </div>

        <!-- Image: natural height, max for very tall images -->
        <Link :href="productUrl" class="relative block w-full bg-muted/40">
            <div class="relative w-full max-h-[480px] min-h-[200px] overflow-hidden">
                <img
                    v-if="product.primary_image_url"
                    :src="product.primary_image_url"
                    :alt="product.name"
                    class="h-full w-full object-contain"
                    loading="lazy"
                />
                <div
                    v-else
                    class="flex aspect-[4/3] w-full items-center justify-center bg-gradient-to-br from-muted to-muted/80"
                >
                    <svg class="h-16 w-16 text-muted-foreground/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <!-- Discount sticker -->
                <div
                    v-if="hasDiscount"
                    class="absolute left-3 top-3 z-10 flex items-center justify-center rounded-xl border-2 border-white bg-rose-500 px-3 py-1.5 text-sm font-bold text-white shadow-lg"
                >
                    -{{ discountPercent }}% OFF
                </div>
                <div
                    v-else-if="product.is_featured"
                    class="absolute left-3 top-3 z-10 rounded-xl border border-white/80 bg-primary px-3 py-1.5 text-xs font-bold text-primary-foreground shadow-md"
                >
                    Featured
                </div>
                <button
                    type="button"
                    class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-background/95 text-foreground shadow-sm transition hover:bg-background hover:text-rose-500"
                    aria-label="Add to wishlist"
                    @click.prevent="emit('wishlist', product)"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </button>
            </div>
        </Link>

        <!-- Caption + price -->
        <div class="border-b border-border/40 px-4 py-3">
            <Link :href="productUrl" class="block">
                <p class="line-clamp-2 text-[15px] font-semibold leading-snug text-foreground hover:text-primary">
                    {{ product.name }}
                </p>
            </Link>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <Price
                    :amount="product.base_price"
                    :compare-at="product.compare_at_price"
                    :show-discount="false"
                    class="text-base font-semibold"
                />
                <Rating v-if="showRating" :value="product.average_rating" :review-count="product.review_count" size="sm" class="text-muted-foreground" />
            </div>
        </div>

        <!-- Interactions bar (feed-style) -->
        <div class="flex items-center border-b border-border/40">
            <button
                type="button"
                class="flex flex-1 items-center justify-center gap-2 py-3 text-sm font-medium text-muted-foreground transition hover:bg-muted/60 hover:text-foreground"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 12.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span>{{ likeCount }}</span>
            </button>
            <button
                type="button"
                class="flex flex-1 items-center justify-center gap-2 py-3 text-sm font-medium text-muted-foreground transition hover:bg-muted/60 hover:text-foreground"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>{{ commentCount }}</span>
            </button>
            <Button
                variant="ghost"
                size="sm"
                class="flex-1 rounded-none py-3 font-semibold text-primary hover:bg-primary/10 hover:text-primary"
                :disabled="!product.is_in_stock"
                @click.prevent="product.is_in_stock && emit('quickAdd', product)"
            >
                <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Add to cart
            </Button>
            <button
                type="button"
                class="rounded p-2 text-muted-foreground transition hover:bg-muted/60 hover:text-rose-500"
                aria-label="Save"
                @click.prevent="emit('wishlist', product)"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </button>
        </div>

        <!-- Comments preview (self-adjusting height) -->
        <div v-if="feedComments.length" class="px-4 py-3">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Comments</p>
            <div class="space-y-3">
                <div v-for="(c, i) in feedComments" :key="i" class="flex gap-3">
                    <PolystarAvatar :name="c.name" size="sm" class="mt-0.5 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-foreground">{{ c.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ c.text }}</p>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>
