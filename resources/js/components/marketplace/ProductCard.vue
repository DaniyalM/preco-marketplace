<script setup lang="ts">
import { Price, Rating } from '@/components/common';
import { Badge, Button, Card, CardContent } from '@/components/ui';
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
const vendorUrl = computed(() => (props.product.vendor ? `/vendors/${props.product.vendor.slug}` : null));

const hasDiscount = computed(() => props.product.compare_at_price != null && props.product.compare_at_price > props.product.base_price);
</script>

<template>
    <Card
        :class="
            cn(
                'group overflow-hidden rounded-2xl border border-border/60 bg-card transition-all duration-300',
                'hover:-translate-y-1 hover:border-border hover:shadow-xl hover:shadow-primary/5',
                $props.class,
            )
        "
    >
        <!-- Image block -->
        <Link :href="productUrl" class="relative block aspect-[4/5] overflow-hidden bg-muted/50">
            <img
                v-if="product.primary_image_url"
                :src="product.primary_image_url"
                :alt="product.name"
                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                loading="lazy"
            />
            <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-muted to-muted/70">
                <svg class="h-16 w-16 text-muted-foreground/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
            </div>

            <!-- Top badges -->
            <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                <Badge
                    v-if="product.is_featured"
                    class="rounded-full border-0 bg-primary/90 px-2.5 py-0.5 text-xs font-medium text-primary-foreground shadow-sm backdrop-blur-sm"
                >
                    Featured
                </Badge>
                <Badge v-if="!product.is_in_stock" variant="secondary" class="rounded-full border-0 px-2.5 py-0.5 text-xs"> Out of stock </Badge>
                <Badge v-else-if="hasDiscount" variant="destructive" class="rounded-full border-0 px-2.5 py-0.5 text-xs font-medium shadow-sm">
                    Sale
                </Badge>
            </div>

            <!-- Wishlist -->
            <button
                type="button"
                class="absolute top-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-background/90 text-muted-foreground shadow-md backdrop-blur-sm transition-all hover:bg-background hover:text-destructive focus:ring-2 focus:ring-primary/20 focus:outline-none"
                aria-label="Add to wishlist"
                @click.prevent="emit('wishlist', product)"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                    />
                </svg>
            </button>

            <!-- Quick add on hover (over image) -->
            <div
                v-if="showQuickAdd && product.is_in_stock"
                class="absolute inset-x-3 bottom-3 translate-y-2 opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100"
            >
                <Button
                    size="sm"
                    class="w-full rounded-full bg-background/95 font-medium shadow-lg backdrop-blur-sm hover:bg-primary hover:text-primary-foreground"
                    @click.prevent="emit('quickAdd', product)"
                >
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add to cart
                </Button>
            </div>
        </Link>

        <CardContent class="flex flex-col gap-3 p-4 sm:p-5">
            <!-- Vendor -->
            <Link
                v-if="showVendor && product.vendor && vendorUrl"
                :href="vendorUrl"
                class="text-xs font-medium tracking-wider text-muted-foreground uppercase transition-colors hover:text-primary"
            >
                {{ product.vendor.business_name }}
            </Link>

            <!-- Title -->
            <Link :href="productUrl" class="focus:ring-0 focus:outline-none">
                <h3 class="line-clamp-2 text-base leading-snug font-semibold text-foreground transition-colors hover:text-primary">
                    {{ product.name }}
                </h3>
            </Link>

            <!-- Rating -->
            <Rating v-if="showRating" :value="product.average_rating" :review-count="product.review_count" size="sm" class="mt-0.5" />

            <!-- Price + desktop quick add -->
            <div class="mt-auto flex items-center justify-between gap-3 pt-1">
                <Price :amount="product.base_price" :compare-at="product.compare_at_price" class="text-base font-semibold" />
                <Button
                    v-if="showQuickAdd && product.is_in_stock"
                    size="sm"
                    variant="outline"
                    class="hidden shrink-0 rounded-full border-2 sm:inline-flex"
                    @click="emit('quickAdd', product)"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
