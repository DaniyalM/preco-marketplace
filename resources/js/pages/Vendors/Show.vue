<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { ProductGrid } from '@/components/marketplace';
import { EmptyState } from '@/components/common';
import {
    Card,
    CardContent,
    Combobox,
    Badge,
    Avatar,
    AvatarImage,
    AvatarFallback,
} from '@/components/ui';
import { useVendorQuery, useVendorProductsQuery } from '@/composables/useVendorsApi';
import { ref, computed } from 'vue';

interface Props {
    slug: string;
}

const props = defineProps<Props>();

interface Vendor {
    id: number;
    business_name: string;
    slug: string;
    logo?: string | null;
    banner?: string | null;
    description?: string | null;
    website?: string | null;
    is_featured: boolean;
    products_count?: number;
}

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
    category?: { name: string; slug: string };
}

const sort = ref('created_at');

const sortOptions = [
    { value: 'created_at', label: 'Newest' },
    { value: 'price', label: 'Price: Low to High' },
    { value: 'price_desc', label: 'Price: High to Low' },
    { value: 'popularity', label: 'Most Popular' },
].map((o) => ({ value: o.value, label: o.label }));

const productsParams = computed(() => {
    if (sort.value === 'price_desc') {
        return { sort: 'price', order: 'desc' };
    }
    return { sort: sort.value, order: sort.value === 'price' ? 'asc' : 'desc' };
});

const { data: vendor, isLoading: loading } = useVendorQuery(() => props.slug);
const { data: productsData, isLoading: productsLoading } = useVendorProductsQuery(
    () => props.slug,
    productsParams
);

const products = computed(() => (Array.isArray(productsData.value) ? productsData.value : []) as Product[]);

const initials = computed(() => {
    const v = vendor.value;
    if (!v?.business_name) return '?';
    const words = (v as Vendor).business_name.split(' ');
    return words.map(w => w[0]).join('').substring(0, 2).toUpperCase();
});
</script>

<template>
    <AppLayout>
        <Head :title="vendor?.business_name || 'Vendor'" />

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <svg class="h-8 w-8 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
        </div>

        <template v-else-if="vendor">
            <!-- Banner -->
            <div class="relative h-48 bg-gradient-to-r from-primary/20 to-primary/5 md:h-64">
                <img
                    v-if="vendor.banner"
                    :src="vendor.banner"
                    :alt="vendor.business_name"
                    class="h-full w-full object-cover"
                />
            </div>

            <!-- Vendor Info -->
            <div class="container mx-auto px-4">
                <div class="relative -mt-16 mb-8 flex flex-col items-center gap-4 md:flex-row md:items-end md:gap-6">
                    <Avatar class="h-32 w-32 border-4 border-background">
                        <AvatarImage v-if="vendor.logo" :src="vendor.logo" />
                        <AvatarFallback class="text-3xl">{{ initials }}</AvatarFallback>
                    </Avatar>
                    
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex items-center justify-center gap-2 md:justify-start">
                            <h1 class="text-2xl font-bold md:text-3xl">{{ vendor.business_name }}</h1>
                            <Badge v-if="vendor.is_featured" variant="secondary">Featured</Badge>
                        </div>
                        <p v-if="vendor.description" class="mt-2 max-w-2xl text-muted-foreground">
                            {{ vendor.description }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ vendor.products_count }} products
                        </p>
                    </div>
                    
                    <a
                        v-if="vendor.website"
                        :href="vendor.website"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-sm text-primary hover:underline"
                    >
                        Visit Website
                    </a>
                </div>

                <!-- Filters -->
                <Card class="mb-6">
                    <CardContent class="flex items-center justify-between p-4">
                        <span class="text-sm text-muted-foreground">
                            Showing {{ products.length }} products
                        </span>
                        <Combobox
                            v-model="sort"
                            :options="sortOptions"
                            placeholder="Sort by"
                            class="w-48"
                            :searchable="true"
                        />
                    </CardContent>
                </Card>

                <!-- Products -->
                <ProductGrid
                    :products="products"
                    :loading="productsLoading"
                    :columns="4"
                    :show-vendor="false"
                    class="pb-12"
                />
            </div>
        </template>

        <!-- Not Found -->
        <div v-else class="container mx-auto px-4 py-8">
            <EmptyState
                icon="store"
                title="Vendor not found"
                description="The vendor you're looking for doesn't exist."
                action-label="Browse Vendors"
                action-href="/vendors"
            />
        </div>
    </AppLayout>
</template>
