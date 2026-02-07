<script setup lang="ts">
import { Price, Rating } from '@/components/common';
import { Button } from '@/components/ui';
import { cn } from '@/lib/utils';
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
    /** Compact layout: smaller image, padding, and text for grid listings */
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showVendor: true,
    showRating: true,
    showQuickAdd: true,
    compact: false,
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
        props.product.compare_at_price > props.product.base_price
);

const discountPercent = computed(() => {
    if (!hasDiscount.value || !props.product.compare_at_price) return 0;
    return Math.round(100 - (props.product.base_price / props.product.compare_at_price) * 100);
});
</script>

<template>
    <article
        :class="
            cn(
                'group relative flex flex-col overflow-hidden bg-card',
                compact ? 'rounded-xl' : 'rounded-2xl',
                'border border-border/50 shadow-sm transition-all duration-300 ease-out',
                'hover:border-border hover:shadow-lg hover:shadow-black/5',
                $props.class,
            )
        "
    >
        <!-- Image -->
        <Link
            :href="productUrl"
            :class="[
                'relative block w-full overflow-hidden bg-muted/40',
                compact ? 'aspect-square' : 'aspect-[3/4]',
            ]"
        >
            <img
                v-if="product.primary_image_url"
                :src="product.primary_image_url"
                :alt="product.name"
                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03]"
                loading="lazy"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-muted/80 to-muted"
            >
                <svg
                    class="h-14 w-14 text-muted-foreground/30"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
            </div>

            <!-- Badges -->
            <div
                :class="[
                    'absolute left-2 top-2 z-10 flex flex-wrap gap-1',
                    !compact && 'left-3 top-3 gap-1.5',
                ]"
            >
                <span
                    v-if="hasDiscount"
                    :class="[
                        'rounded bg-rose-500 font-semibold text-white shadow-sm',
                        compact ? 'px-1.5 py-0.5 text-[10px]' : 'rounded-md px-2 py-0.5 text-xs',
                    ]"
                >
                    -{{ discountPercent }}%
                </span>
                <span
                    v-if="product.is_featured && !hasDiscount"
                    :class="[
                        'rounded font-semibold text-primary-foreground',
                        compact ? 'bg-primary px-1.5 py-0.5 text-[10px]' : 'rounded-md bg-primary px-2 py-0.5 text-xs',
                    ]"
                >
                    Featured
                </span>
                <span
                    v-if="!product.is_in_stock"
                    :class="[
                        'rounded bg-muted-foreground/90 font-medium text-white',
                        compact ? 'px-1.5 py-0.5 text-[10px]' : 'rounded-md px-2 py-0.5 text-xs',
                    ]"
                >
                    Out of stock
                </span>
            </div>

            <!-- Wishlist -->
            <button
                type="button"
                :class="[
                    'absolute right-2 top-2 z-10 flex items-center justify-center rounded-full bg-background/90 text-foreground shadow-sm backdrop-blur-sm transition hover:bg-background hover:text-rose-500 focus:outline-none focus:ring-2 focus:ring-primary/30',
                    compact ? 'h-7 w-7' : 'right-3 top-3 h-9 w-9',
                ]"
                aria-label="Add to wishlist"
                @click.prevent="emit('wishlist', product)"
            >
                <svg :class="compact ? 'h-4 w-4' : 'h-5 w-5'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                    />
                </svg>
            </button>

            <!-- Quick add overlay (hover) -->
            <div
                v-if="showQuickAdd && product.is_in_stock"
                :class="[
                    'absolute inset-x-0 bottom-0 z-10 translate-y-full bg-gradient-to-t from-black/70 to-transparent opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100',
                    compact ? 'p-2' : 'p-3',
                ]"
            >
                <Button
                    size="sm"
                    class="w-full rounded-full bg-background font-semibold text-foreground shadow-md hover:bg-primary hover:text-primary-foreground"
                    @click.prevent="emit('quickAdd', product)"
                >
                    <svg :class="compact ? 'mr-1.5 h-3.5 w-3.5' : 'mr-2 h-4 w-4'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add to cart
                </Button>
            </div>
        </Link>

        <!-- Content -->
        <div
            :class="[
                'flex flex-1 flex-col',
                compact ? 'p-2.5' : 'p-4',
            ]"
        >
            <Link
                v-if="showVendor && product.vendor && vendorUrl"
                :href="vendorUrl"
                :class="[
                    'mb-0.5 font-medium uppercase tracking-wider text-muted-foreground transition hover:text-primary',
                    compact ? 'text-[10px]' : 'text-xs',
                ]"
            >
                {{ product.vendor.business_name }}
            </Link>

            <Link :href="productUrl" class="focus:outline-none focus:ring-0">
                <h3
                    :class="[
                        'line-clamp-2 font-semibold leading-snug text-foreground transition hover:text-primary',
                        compact ? 'text-xs' : 'text-[15px]',
                    ]"
                >
                    {{ product.name }}
                </h3>
            </Link>

            <Rating
                v-if="showRating"
                :value="product.average_rating"
                :review-count="product.review_count"
                size="sm"
                :class="compact ? 'mt-1' : 'mt-2'"
            />

            <div
                :class="[
                    'mt-auto flex items-center justify-between gap-2',
                    compact ? 'pt-2' : 'pt-3',
                ]"
            >
                <Price
                    :amount="product.base_price"
                    :compare-at="product.compare_at_price"
                    :show-discount="false"
                    :class="compact ? 'text-sm font-semibold' : 'text-base font-semibold'"
                />
                <Button
                    v-if="showQuickAdd && product.is_in_stock"
                    size="icon"
                    variant="ghost"
                    :class="[
                        'shrink-0 rounded-full border border-border hover:bg-primary hover:border-primary hover:text-primary-foreground',
                        compact ? 'h-7 w-7' : 'h-9 w-9',
                    ]"
                    aria-label="Add to cart"
                    @click.prevent="emit('quickAdd', product)"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </Button>
            </div>
        </div>
    </article>
</template>
