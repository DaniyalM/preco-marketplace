<script setup lang="ts">
import { EmptyState, Price, Rating } from '@/components/common';
import { AppLayout } from '@/components/layouts';
import { Badge, Button, Separator } from '@/components/ui';
import { SeoHead, generateBreadcrumbJsonLd, generateProductJsonLd } from '@/composables/useSeoMeta';
import { useToastStore } from '@/stores/toast';
import { useProductQuery } from '@/composables/useProductsApi';
import { useAddCartItemMutation } from '@/composables/useCartApi';
import { useToggleWishlistMutation } from '@/composables/useWishlistApi';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const toast = useToastStore();

interface ProductImage {
    id: number;
    url: string;
    path?: string;
    thumbnail_path?: string;
    alt_text?: string;
    is_primary: boolean;
}

interface ProductOption {
    id: number;
    name: string;
    values: Array<{
        id: number;
        value: string;
        label: string;
        color_code?: string | null;
    }>;
}

interface ProductVariant {
    id: number;
    sku: string;
    name: string;
    price: number;
    compare_at_price?: number | null;
    stock_quantity: number;
    is_in_stock: boolean;
    option_values: Record<string, string>;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string;
    short_description?: string;
    description?: string;
    base_price: number;
    compare_at_price?: number | null;
    current_price: number;
    has_discount: boolean;
    discount_percentage?: number;
    is_in_stock: boolean;
    has_variants: boolean;
    average_rating: number;
    review_count: number;
    vendor?: {
        id: number;
        business_name: string;
        slug: string;
        logo?: string;
        description?: string;
    };
    category?: {
        id: number;
        name: string;
        slug: string;
    };
    images: ProductImage[];
    options: ProductOption[];
    variants: ProductVariant[];
}

interface SeoMeta {
    title: string;
    description: string;
    image?: string;
    type: string;
    price?: { amount: number; currency: string };
    availability?: string;
    brand?: string;
    category?: string;
    rating?: { value: number; count: number };
}

interface Props {
    slug: string;
    product?: Product | null;
    seo?: SeoMeta | null;
}

const props = defineProps<Props>();

const productQuery = useProductQuery(
    computed(() => (props.product ? undefined : props.slug)),
    { enabled: computed(() => !props.product) }
);

const product = computed(
    () => (props.product ?? productQuery.data.value ?? null) as Product | null
);
const loading = computed(() => !props.product && productQuery.isLoading.value);
const error = computed(
    () => (productQuery.error.value ? String(productQuery.error.value) : null)
);

const selectedOptions = ref<Record<string, string>>({});
const quantity = ref(1);
const selectedImageIndex = ref(0);

// Initialize selected options from product
const initializeOptions = () => {
    if (product.value?.options) {
        product.value.options.forEach((option) => {
            if (option.values.length > 0) {
                selectedOptions.value[option.name] = option.values[0].value;
            }
        });
    }
};

// Initialize options when product is available
watch(
    product,
    (p) => {
        if (p) initializeOptions();
    },
    { immediate: true }
);

const selectedVariant = computed(() => {
    if (!product.value?.has_variants || !product.value?.variants.length) {
        return null;
    }

    return product.value.variants.find((variant) => {
        return Object.entries(selectedOptions.value).every(([key, value]) => {
            return variant.option_values[key] === value;
        });
    });
});

const currentPrice = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.price;
    }
    return product.value?.current_price ?? product.value?.base_price ?? 0;
});

const compareAtPrice = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.compare_at_price;
    }
    return product.value?.compare_at_price;
});

const isInStock = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.is_in_stock;
    }
    return product.value?.is_in_stock ?? false;
});

const canAddToCart = computed(() => {
    if (!product.value) return false;
    if (!isInStock.value) return false;
    if (product.value.has_variants && !selectedVariant.value) return false;
    return true;
});

// Get image URL (handle both direct URL and path)
const getImageUrl = (image: ProductImage): string => {
    if (image.url) return image.url;
    if (image.path) {
        if (image.path.startsWith('http://') || image.path.startsWith('https://')) {
            return image.path;
        }
        return `/storage/${image.path}`;
    }
    return '';
};

const primaryImage = computed(() => {
    if (!product.value?.images?.length) return null;
    const primary = product.value.images.find((i) => i.is_primary);
    return primary || product.value.images[0];
});

// JSON-LD structured data for SEO
const productJsonLd = computed(() => {
    if (!product.value) return '';

    const images = product.value.images.map((img) => getImageUrl(img)).filter(Boolean);

    return generateProductJsonLd({
        name: product.value.name,
        description: product.value.short_description || product.value.description || '',
        image: images.length ? images : ['/images/placeholder.png'],
        price: product.value.base_price,
        currency: 'USD',
        availability: isInStock.value ? 'InStock' : 'OutOfStock',
        brand: product.value.vendor?.business_name,
        sku: product.value.sku,
        rating:
            product.value.review_count > 0
                ? {
                      value: product.value.average_rating,
                      count: product.value.review_count,
                  }
                : undefined,
        url: typeof window !== 'undefined' ? window.location.href : undefined,
    });
});

const breadcrumbJsonLd = computed(() => {
    const items = [
        { name: 'Home', url: '/' },
        { name: 'Products', url: '/products' },
    ];

    if (product.value?.category) {
        items.push({
            name: product.value.category.name,
            url: `/categories/${product.value.category.slug}`,
        });
    }

    if (product.value) {
        items.push({
            name: product.value.name,
            url: `/products/${product.value.slug}`,
        });
    }

    return generateBreadcrumbJsonLd(items);
});

const selectOption = (optionName: string, value: string) => {
    selectedOptions.value[optionName] = value;
};

const addToCartMutation = useAddCartItemMutation();
const toggleWishlistMutation = useToggleWishlistMutation();

const addToCart = async () => {
    if (!canAddToCart.value || !product.value) return;
    try {
        await addToCartMutation.mutateAsync({
            product_id: product.value.id,
            variant_id: selectedVariant.value?.id,
            quantity: quantity.value,
        });
        toast.success('Added to cart');
    } catch {
        toast.error('Failed to add to cart');
    }
};

const addToWishlist = async () => {
    if (!product.value) return;
    try {
        await toggleWishlistMutation.mutateAsync({ productId: product.value.id });
        toast.success('Added to wishlist');
    } catch {
        toast.error('Failed to update wishlist');
    }
};
</script>

<template>
    <AppLayout>
        <!-- SEO Head with structured data -->
        <SeoHead
            v-if="product && seo"
            :title="seo.title"
            :description="seo.description || ''"
            :image="seo.image"
            :type="'product'"
            :price="seo.price"
            :availability="seo.availability as any"
            :brand="seo.brand"
            :category="seo.category"
            :rating="seo.rating"
            :url="`/products/${slug}`"
        />
        <Head v-else :title="product?.name || 'Product'" />

        <!-- JSON-LD Structured Data (for search engines) -->
        <component :is="'script'" v-if="product" type="application/ld+json" v-html="productJsonLd" />
        <component :is="'script'" v-if="product" type="application/ld+json" v-html="breadcrumbJsonLd" />

        <div class="container mx-auto px-4 py-8">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <svg class="h-8 w-8 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
            </div>

            <!-- Error State -->
            <EmptyState
                v-else-if="error"
                icon="box"
                :title="error"
                description="The product you're looking for might have been removed or is temporarily unavailable."
                action-label="Browse Products"
                action-href="/products"
            />

            <!-- Product Content -->
            <div v-else-if="product" class="grid gap-8 lg:grid-cols-2">
                <!-- Image Gallery -->
                <div class="space-y-4">
                    <!-- Main Image -->
                    <div class="aspect-square overflow-hidden rounded-lg bg-muted">
                        <img
                            v-if="product.images.length > 0"
                            :src="getImageUrl(product.images[selectedImageIndex])"
                            :alt="product.images[selectedImageIndex].alt_text || product.name"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full items-center justify-center">
                            <svg class="h-24 w-24 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Thumbnails -->
                    <div v-if="product.images.length > 1" class="flex gap-2 overflow-x-auto">
                        <button
                            v-for="(image, index) in product.images"
                            :key="image.id"
                            type="button"
                            class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border-2 transition-colors"
                            :class="selectedImageIndex === index ? 'border-primary' : 'border-transparent'"
                            @click="selectedImageIndex = index"
                        >
                            <img
                                :src="getImageUrl(image)"
                                :alt="image.alt_text || `${product.name} - Image ${index + 1}`"
                                class="h-full w-full object-cover"
                            />
                        </button>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="space-y-6">
                    <!-- Breadcrumb (visible & SEO friendly) -->
                    <nav class="flex text-sm text-muted-foreground" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2" itemscope itemtype="https://schema.org/BreadcrumbList">
                            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                                <Link href="/" itemprop="item" class="hover:text-foreground">
                                    <span itemprop="name">Home</span>
                                </Link>
                                <meta itemprop="position" content="1" />
                            </li>
                            <li><span class="mx-2">/</span></li>
                            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                                <Link href="/products" itemprop="item" class="hover:text-foreground">
                                    <span itemprop="name">Products</span>
                                </Link>
                                <meta itemprop="position" content="2" />
                            </li>
                            <template v-if="product.category">
                                <li><span class="mx-2">/</span></li>
                                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                                    <Link :href="`/categories/${product.category.slug}`" itemprop="item" class="hover:text-foreground">
                                        <span itemprop="name">{{ product.category.name }}</span>
                                    </Link>
                                    <meta itemprop="position" content="3" />
                                </li>
                            </template>
                        </ol>
                    </nav>

                    <!-- Title -->
                    <div>
                        <h1 class="text-3xl font-bold" itemprop="name">{{ product.name }}</h1>
                        <Link v-if="product.vendor" :href="`/vendors/${product.vendor.slug}`" class="text-muted-foreground hover:text-primary">
                            by {{ product.vendor.business_name }}
                        </Link>
                    </div>

                    <!-- Rating -->
                    <Rating :value="product.average_rating" :review-count="product.review_count" show-value />

                    <!-- Price -->
                    <Price :amount="currentPrice" :compare-at="compareAtPrice" size="xl" />

                    <!-- Short Description -->
                    <p v-if="product.short_description" class="text-muted-foreground" itemprop="description">
                        {{ product.short_description }}
                    </p>

                    <Separator />

                    <!-- Options -->
                    <div v-if="product.has_variants && product.options.length > 0" class="space-y-4">
                        <div v-for="option in product.options" :key="option.id">
                            <label class="mb-2 block text-sm font-medium"> {{ option.name }}: {{ selectedOptions[option.name] }} </label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="value in option.values"
                                    :key="value.id"
                                    type="button"
                                    class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                                    :class="
                                        selectedOptions[option.name] === value.value
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-input hover:border-primary'
                                    "
                                    :style="value.color_code ? { backgroundColor: value.color_code } : {}"
                                    @click="selectOption(option.name, value.value)"
                                >
                                    <span v-if="!value.color_code">{{ value.label || value.value }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity & Add to Cart -->
                    <div class="flex items-center gap-4">
                        <div class="flex items-center rounded-lg border">
                            <button type="button" class="px-4 py-2 hover:bg-muted" :disabled="quantity <= 1" @click="quantity--">-</button>
                            <span class="w-12 text-center">{{ quantity }}</span>
                            <button type="button" class="px-4 py-2 hover:bg-muted" @click="quantity++">+</button>
                        </div>

                        <Button size="lg" class="flex-1" :disabled="!canAddToCart" @click="addToCart">
                            <template v-if="!isInStock">Out of Stock</template>
                            <template v-else-if="product.has_variants && !selectedVariant">Select Options</template>
                            <template v-else>Add to Cart</template>
                        </Button>

                        <Button variant="outline" size="lg" @click="addToWishlist">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                />
                            </svg>
                        </Button>
                    </div>

                    <!-- Stock Badge -->
                    <Badge v-if="isInStock" variant="success">In Stock</Badge>
                    <Badge v-else variant="secondary">Out of Stock</Badge>

                    <!-- SKU -->
                    <p class="text-sm text-muted-foreground">
                        SKU: <span itemprop="sku">{{ selectedVariant?.sku || product.sku }}</span>
                    </p>

                    <Separator />

                    <!-- Description -->
                    <div v-if="product.description">
                        <h2 class="mb-4 text-lg font-semibold">Description</h2>
                        <div class="prose prose-sm max-w-none text-muted-foreground" v-html="product.description" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
