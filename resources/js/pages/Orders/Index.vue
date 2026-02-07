<script setup lang="ts">
import { EmptyState, Price, StatusBadge } from '@/components/common';
import { AppLayout } from '@/components/layouts';
import { Button, Card, CardContent } from '@/components/ui';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Order {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    total: number;
    item_count: number;
    created_at: string;
}

const orders = ref<Order[]>([]);
const loading = ref(true);

const fetchOrders = async () => {
    loading.value = true;
    try {
        // This would be an API call in a real app
        // const response = await axios.get('/api/orders');
        // orders.value = response.data.data;
        orders.value = []; // Empty for now
    } catch (error) {
        console.error('Failed to fetch orders', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchOrders);

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout title="My Orders">
        <Head title="My Orders" />

        <div class="container mx-auto px-4 py-8">
            <!-- <h1 class="mb-8 text-3xl font-bold">My Orders</h1> -->

            <!-- Loading -->
            <div v-if="loading" class="space-y-4">
                <Card v-for="i in 3" :key="i" class="animate-pulse">
                    <CardContent class="p-6">
                        <div class="flex justify-between">
                            <div class="space-y-2">
                                <div class="h-4 w-32 rounded bg-muted" />
                                <div class="h-3 w-24 rounded bg-muted" />
                            </div>
                            <div class="h-6 w-20 rounded bg-muted" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-else-if="orders.length === 0"
                icon="order"
                title="No orders yet"
                description="You haven't placed any orders yet. Start shopping to see your orders here."
                action-label="Start Shopping"
                action-href="/products"
            />

            <!-- Orders List -->
            <div v-else class="space-y-4">
                <Card v-for="order in orders" :key="order.id">
                    <CardContent class="p-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="flex items-center gap-3">
                                    <h3 class="font-semibold">{{ order.order_number }}</h3>
                                    <StatusBadge :status="order.status" type="order" />
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">Placed on {{ formatDate(order.created_at) }}</p>
                                <p class="text-sm text-muted-foreground">{{ order.item_count }} items</p>
                            </div>

                            <div class="flex items-center gap-4">
                                <Price :amount="order.total" size="lg" />
                                <Link :href="`/orders/${order.id}`">
                                    <Button variant="outline">View Details</Button>
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
