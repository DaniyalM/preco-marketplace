<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { ProductGrid } from '@/components/marketplace';
import { CategoryCard } from '@/components/marketplace';
import { EmptyState } from '@/components/common';
import { Card, CardContent, Select, Button } from '@/components/ui';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

interface Props {
    slug: string;
}

const props = defineProps<Props>();

interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    image?: string | null;
    products_count?: number;
    parent?: { name: string; slug: string } | null;
    children?: Category[];
    breadcrumb?: Array<{ name: string; slug: string }>;
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
    vendor?: { business_name: string; slug: string };
}

const category = ref<Category | null>(null);
const products = ref<Product[]>([]);
const loading = ref(true);
const productsLoading = ref(true);
const sort = ref('created_at');

const sortOptions = [
    { value: 'created_at', label: 'Newest' },
    { value: 'price', label: 'Price: Low to High' },
    { value: 'price_desc', label: 'Price: High to Low' },
    { value: 'popularity', label: 'Most Popular' },
];

const fetchCategory = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/public/categories/${props.slug}`);
        category.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch category', error);
    } finally {
        loading.value = false;
    }
};

const fetchProducts = async () => {
    productsLoading.value = true;
    try {
        const params: Record<string, string> = {};
        if (sort.value === 'price_desc') {
            params.sort = 'price';
            params.order = 'desc';
        } else {
            params.sort = sort.value;
            params.order = sort.value === 'price' ? 'asc' : 'desc';
        }
        
        const response = await axios.get(`/api/public/categories/${props.slug}/products`, { params });
        products.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch products', error);
    } finally {
        productsLoading.value = false;
    }
};

onMounted(() => {
    fetchCategory();
    fetchProducts();
});

watch(sort, fetchProducts);
</script>

<template>
    <AppLayout>
        <Head :title="category?.name || 'Category'" />

        <div class="container mx-auto px-4 py-8">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <svg class="h-8 w-8 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>

            <template v-else-if="category">
                <!-- Breadcrumb -->
                <nav class="mb-6 flex text-sm text-muted-foreground">
                    <Link href="/categories" class="hover:text-foreground">Categories</Link>
                    <template v-if="category.breadcrumb">
                        <template v-for="crumb in category.breadcrumb" :key="crumb.slug">
                            <span class="mx-2">/</span>
                            <Link :href="`/categories/${crumb.slug}`" class="hover:text-foreground">
                                {{ crumb.name }}
                            </Link>
                        </template>
                    </template>
                </nav>

                <!-- Category Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold">{{ category.name }}</h1>
                    <p v-if="category.description" class="mt-2 text-muted-foreground">
                        {{ category.description }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ category.products_count }} products
                    </p>
                </div>

                <!-- Subcategories -->
                <div v-if="category.children && category.children.length > 0" class="mb-8">
                    <h2 class="mb-4 text-lg font-semibold">Subcategories</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <CategoryCard
                            v-for="child in category.children"
                            :key="child.id"
                            :category="child"
                            variant="compact"
                        />
                    </div>
                </div>

                <!-- Filters -->
                <Card class="mb-6">
                    <CardContent class="flex items-center justify-between p-4">
                        <span class="text-sm text-muted-foreground">
                            Showing {{ products.length }} products
                        </span>
                        <Select v-model="sort" class="w-48">
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </Select>
                    </CardContent>
                </Card>

                <!-- Products -->
                <ProductGrid
                    :products="products"
                    :loading="productsLoading"
                    :columns="4"
                />
            </template>

            <!-- Not Found -->
            <EmptyState
                v-else
                icon="search"
                title="Category not found"
                description="The category you're looking for doesn't exist."
                action-label="Browse Categories"
                action-href="/categories"
            />
        </div>
    </AppLayout>
</template>
