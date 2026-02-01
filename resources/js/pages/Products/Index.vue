<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { ProductGrid } from '@/components/marketplace';
import {
    Card,
    CardContent,
    Input,
    Select,
    Button,
} from '@/components/ui';
import { ref, onMounted } from 'vue';
import axios from 'axios';

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

const products = ref<Product[]>([]);
const loading = ref(true);
const search = ref('');
const sort = ref('created_at');

const sortOptions = [
    { value: 'created_at', label: 'Newest' },
    { value: 'price', label: 'Price: Low to High' },
    { value: 'price_desc', label: 'Price: High to Low' },
    { value: 'popularity', label: 'Most Popular' },
    { value: 'rating', label: 'Top Rated' },
];

const fetchProducts = async () => {
    loading.value = true;
    try {
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
        
        const response = await axios.get('/api/public/products', { params });
        products.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch products', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchProducts);

const handleQuickAdd = (product: Product) => {
    console.log('Quick add:', product);
    // TODO: Implement quick add to cart
};

const handleWishlist = (product: Product) => {
    console.log('Wishlist toggle:', product);
    // TODO: Implement wishlist toggle
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
                            <Input
                                v-model="search"
                                placeholder="Search products..."
                                @keyup.enter="fetchProducts"
                            />
                        </div>
                        <Select v-model="sort" class="w-48" @change="fetchProducts">
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </Select>
                        <Button @click="fetchProducts">Search</Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Products Grid -->
            <ProductGrid
                :products="products"
                :loading="loading"
                :columns="4"
                @quick-add="handleQuickAdd"
                @wishlist="handleWishlist"
            />
        </div>
    </AppLayout>
</template>
