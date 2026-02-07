<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { VendorLayout } from '@/components/layouts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Button,
    Separator,
} from '@/components/ui';
import { FormField, ImageUpload } from '@/components/common';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
    parent_id?: number | null;
}

interface Props {
    categories: Category[];
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    short_description: '',
    description: '',
    category_id: '',
    base_price: '',
    compare_at_price: '',
    cost_price: '',
    sku: '',
    track_inventory: true,
    stock_quantity: '0',
    low_stock_threshold: '5',
    allow_backorder: false,
    product_type: 'physical',
    weight: '',
    length: '',
    width: '',
    height: '',
    status: 'draft',
    images: [] as File[],
});

const categoryOptions = props.categories.map(cat => ({
    value: cat.id.toString(),
    label: cat.name,
}));

const productTypeOptions = [
    { value: 'physical', label: 'Physical Product' },
    { value: 'digital', label: 'Digital Product' },
    { value: 'service', label: 'Service' },
];

const statusOptions = [
    { value: 'draft', label: 'Save as Draft' },
    { value: 'active', label: 'Publish Now' },
];

const imageFiles = ref<File[]>([]);

const handleImageUpload = (file: File) => {
    imageFiles.value.push(file);
    form.images = imageFiles.value;
};

const submit = () => {
    form.post('/vendor/products', {
        forceFormData: true,
    });
};
</script>

<template>
    <VendorLayout title="Add Product">
        <Head title="Add Product - Vendor Dashboard" />

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Add Product</h1>
                    <p class="text-muted-foreground">Create a new product for your store</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/vendor/products">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                    <Button type="submit" :loading="form.processing">
                        {{ form.status === 'active' ? 'Publish Product' : 'Save Draft' }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Basic Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <FormField
                                v-model="form.name"
                                label="Product Name"
                                placeholder="Enter product name"
                                :error="form.errors.name"
                                required
                            />

                            <FormField
                                v-model="form.short_description"
                                type="textarea"
                                label="Short Description"
                                placeholder="Brief description for product cards"
                                :rows="2"
                                :error="form.errors.short_description"
                                hint="Displayed in product listings and search results"
                            />

                            <FormField
                                v-model="form.description"
                                type="textarea"
                                label="Full Description"
                                placeholder="Detailed product description..."
                                :rows="6"
                                :error="form.errors.description"
                            />
                        </CardContent>
                    </Card>

                    <!-- Images -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Images</CardTitle>
                            <CardDescription>Upload product images. First image will be the primary.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-3">
                                <ImageUpload
                                    v-for="i in 3"
                                    :key="i"
                                    aspect-ratio="square"
                                    :placeholder="i === 1 ? 'Primary image' : 'Add image'"
                                    @file="handleImageUpload"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pricing -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Pricing</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid gap-6 md:grid-cols-3">
                                <FormField
                                    v-model="form.base_price"
                                    type="number"
                                    label="Price"
                                    placeholder="0.00"
                                    :error="form.errors.base_price"
                                    required
                                />

                                <FormField
                                    v-model="form.compare_at_price"
                                    type="number"
                                    label="Compare at Price"
                                    placeholder="0.00"
                                    :error="form.errors.compare_at_price"
                                    hint="Original price for showing discounts"
                                />

                                <FormField
                                    v-model="form.cost_price"
                                    type="number"
                                    label="Cost Price"
                                    placeholder="0.00"
                                    :error="form.errors.cost_price"
                                    hint="For profit calculation"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Inventory -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Inventory</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <FormField
                                v-model="form.sku"
                                label="SKU"
                                placeholder="Auto-generated if empty"
                                :error="form.errors.sku"
                                hint="Stock Keeping Unit - unique identifier"
                            />

                            <div class="flex items-center gap-2">
                                <input
                                    id="track_inventory"
                                    v-model="form.track_inventory"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-input"
                                />
                                <label for="track_inventory" class="text-sm font-medium">
                                    Track inventory quantity
                                </label>
                            </div>

                            <div v-if="form.track_inventory" class="grid gap-6 md:grid-cols-2">
                                <FormField
                                    v-model="form.stock_quantity"
                                    type="number"
                                    label="Stock Quantity"
                                    :error="form.errors.stock_quantity"
                                />

                                <FormField
                                    v-model="form.low_stock_threshold"
                                    type="number"
                                    label="Low Stock Threshold"
                                    :error="form.errors.low_stock_threshold"
                                    hint="Get alerts when stock drops below"
                                />
                            </div>

                            <div class="flex items-center gap-2">
                                <input
                                    id="allow_backorder"
                                    v-model="form.allow_backorder"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-input"
                                />
                                <label for="allow_backorder" class="text-sm font-medium">
                                    Allow backorders when out of stock
                                </label>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Shipping -->
                    <Card v-if="form.product_type === 'physical'">
                        <CardHeader>
                            <CardTitle>Shipping</CardTitle>
                            <CardDescription>Physical product dimensions for shipping calculation</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-6 md:grid-cols-4">
                                <FormField
                                    v-model="form.weight"
                                    type="number"
                                    label="Weight (kg)"
                                    placeholder="0.0"
                                    :error="form.errors.weight"
                                />
                                <FormField
                                    v-model="form.length"
                                    type="number"
                                    label="Length (cm)"
                                    placeholder="0"
                                    :error="form.errors.length"
                                />
                                <FormField
                                    v-model="form.width"
                                    type="number"
                                    label="Width (cm)"
                                    placeholder="0"
                                    :error="form.errors.width"
                                />
                                <FormField
                                    v-model="form.height"
                                    type="number"
                                    label="Height (cm)"
                                    placeholder="0"
                                    :error="form.errors.height"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <FormField
                                v-model="form.status"
                                type="select"
                                :options="statusOptions"
                                :error="form.errors.status"
                            />
                        </CardContent>
                    </Card>

                    <!-- Organization -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Organization</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <FormField
                                v-model="form.product_type"
                                type="select"
                                label="Product Type"
                                :options="productTypeOptions"
                                :error="form.errors.product_type"
                            />

                            <FormField
                                v-model="form.category_id"
                                type="select"
                                label="Category"
                                placeholder="Select category"
                                :options="categoryOptions"
                                :error="form.errors.category_id"
                            />
                        </CardContent>
                    </Card>

                    <!-- Help -->
                    <Card>
                        <CardContent class="p-4">
                            <h4 class="font-medium">Tips for a great listing</h4>
                            <ul class="mt-2 space-y-1 text-sm text-muted-foreground">
                                <li>• Use high-quality images</li>
                                <li>• Write detailed descriptions</li>
                                <li>• Set competitive pricing</li>
                                <li>• Keep inventory updated</li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </form>
    </VendorLayout>
</template>
