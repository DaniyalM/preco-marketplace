<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { VendorLayout } from '@/components/layouts';
import { EmptyState } from '@/components/common';
import {
    Card,
    CardContent,
} from '@/components/ui';
</script>

<template>
    <VendorLayout title="Orders">
        <Head title="Orders - Vendor Dashboard" />

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold">Orders</h1>
                <p class="text-muted-foreground">Manage and fulfill your orders</p>
            </div>

            <Card>
                <CardContent class="p-12">
                    <EmptyState
                        icon="order"
                        title="No orders yet"
                        description="When customers order your products, they'll appear here."
                    />
                </CardContent>
            </Card>
        </div>
    </VendorLayout>
</template>
