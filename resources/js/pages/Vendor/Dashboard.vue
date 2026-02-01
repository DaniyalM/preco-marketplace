<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { VendorLayout } from '@/components/layouts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Button,
} from '@/components/ui';
import { StatusBadge, Price, EmptyState } from '@/components/common';
import { Link } from '@inertiajs/vue3';

interface Props {
    stats: {
        total_products: number;
        active_products: number;
        pending_orders: number;
        total_revenue: number;
    };
    recentOrders: Array<{
        id: number;
        order_number: string;
        product_name: string;
        quantity: number;
        total: number;
        fulfillment_status: string;
        created_at: string;
    }>;
    lowStockProducts: Array<{
        id: number;
        name: string;
        sku: string;
        stock_quantity: number;
        low_stock_threshold: number;
    }>;
}

defineProps<Props>();
</script>

<template>
    <VendorLayout title="Dashboard">
        <Head title="Vendor Dashboard" />

        <div class="space-y-6">
            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Products</CardTitle>
                        <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_products }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.active_products }} active
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending Orders</CardTitle>
                        <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending_orders }}</div>
                        <p class="text-xs text-muted-foreground">
                            Awaiting fulfillment
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                        <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">${{ stats.total_revenue.toFixed(2) }}</div>
                        <p class="text-xs text-muted-foreground">
                            After commission
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Low Stock</CardTitle>
                        <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ lowStockProducts.length }}</div>
                        <p class="text-xs text-muted-foreground">
                            Products need restock
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Orders -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Recent Orders</CardTitle>
                                <CardDescription>Latest orders to fulfill</CardDescription>
                            </div>
                            <Link href="/vendor/orders">
                                <Button variant="outline" size="sm">View All</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <EmptyState
                            v-if="recentOrders.length === 0"
                            icon="order"
                            title="No orders yet"
                            description="Orders will appear here once customers start buying."
                        />

                        <div v-else class="space-y-4">
                            <div
                                v-for="order in recentOrders"
                                :key="order.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">{{ order.order_number }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ order.product_name }} × {{ order.quantity }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <Price :amount="order.total" size="sm" />
                                    <StatusBadge :status="order.fulfillment_status" type="order" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Low Stock Products -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Low Stock Alert</CardTitle>
                                <CardDescription>Products running low on inventory</CardDescription>
                            </div>
                            <Link href="/vendor/products">
                                <Button variant="outline" size="sm">Manage</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <EmptyState
                            v-if="lowStockProducts.length === 0"
                            icon="box"
                            title="All stocked up"
                            description="No products are running low on inventory."
                        />

                        <div v-else class="space-y-4">
                            <div
                                v-for="product in lowStockProducts"
                                :key="product.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">{{ product.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        SKU: {{ product.sku }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-destructive">
                                        {{ product.stock_quantity }} left
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Threshold: {{ product.low_stock_threshold }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4">
                        <Link href="/vendor/products/create">
                            <Button>
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Product
                            </Button>
                        </Link>
                        <Link href="/vendor/orders">
                            <Button variant="outline">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                View Orders
                            </Button>
                        </Link>
                        <Link href="/vendor/settings">
                            <Button variant="outline">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Settings
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </VendorLayout>
</template>
