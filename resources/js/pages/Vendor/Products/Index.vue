<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { VendorLayout } from '@/components/layouts';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button,
    Badge,
    Input,
    Select,
} from '@/components/ui';
import { StatusBadge, Price, EmptyState, DataTable } from '@/components/common';
import { ref, watch } from 'vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string;
    base_price: number;
    stock_quantity: number;
    status: string;
    has_variants: boolean;
    variants_count: number;
    category?: string;
    image?: string;
    created_at: string;
}

interface Props {
    products: {
        data: Product[];
        links: any;
        meta: any;
    };
    filters: {
        status?: string;
        search?: string;
        sort?: string;
        order?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

const statusOptions = [
    { value: 'all', label: 'All Status' },
    { value: 'draft', label: 'Draft' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const columns = [
    { key: 'name', label: 'Product', sortable: true },
    { key: 'sku', label: 'SKU', sortable: true },
    { key: 'base_price', label: 'Price', sortable: true },
    { key: 'stock_quantity', label: 'Stock', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: '', class: 'w-20' },
];

const applyFilters = () => {
    router.get('/vendor/products', {
        search: search.value || undefined,
        status: status.value !== 'all' ? status.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Debounce search
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(status, applyFilters);
</script>

<template>
    <VendorLayout title="Products">
        <Head title="Products - Vendor Dashboard" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Products</h1>
                    <p class="text-muted-foreground">Manage your product catalog</p>
                </div>
                <Link href="/vendor/products/create">
                    <Button>
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Product
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <div class="flex-1">
                            <Input
                                v-model="search"
                                placeholder="Search products..."
                                class="max-w-sm"
                            />
                        </div>
                        <Select v-model="status" class="w-40">
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Products Table -->
            <Card>
                <CardContent class="p-0">
                    <EmptyState
                        v-if="products.data.length === 0 && !search"
                        icon="box"
                        title="No products yet"
                        description="Start by adding your first product to your store."
                        action-label="Add Product"
                        action-href="/vendor/products/create"
                        class="py-12"
                    />

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Product</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">SKU</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Price</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Stock</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="product in products.data"
                                    :key="product.id"
                                    class="border-b last:border-0 hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 overflow-hidden rounded-lg bg-muted">
                                                <img
                                                    v-if="product.image"
                                                    :src="product.image"
                                                    :alt="product.name"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div>
                                                <Link :href="`/vendor/products/${product.id}`" class="font-medium hover:text-primary">
                                                    {{ product.name }}
                                                </Link>
                                                <div v-if="product.has_variants" class="text-xs text-muted-foreground">
                                                    {{ product.variants_count }} variants
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ product.sku }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Price :amount="product.base_price" size="sm" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="product.stock_quantity <= 5 ? 'text-destructive' : ''">
                                            {{ product.stock_quantity }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :status="product.status" type="product" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link :href="`/vendor/products/${product.id}/edit`">
                                            <Button variant="ghost" size="sm">Edit</Button>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </VendorLayout>
</template>
