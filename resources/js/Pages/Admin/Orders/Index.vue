<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AdminLayout } from '@/components/layouts';
import { EmptyState } from '@/components/common';
import {
    Card,
    CardContent,
} from '@/components/ui';
</script>

<template>
    <AdminLayout title="Orders">
        <Head title="Orders - Admin" />

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold">All Orders</h1>
                <p class="text-muted-foreground">View and manage all marketplace orders</p>
            </div>

            <Card>
                <CardContent class="p-12">
                    <EmptyState
                        icon="order"
                        title="No orders yet"
                        description="Orders will appear here once customers start purchasing."
                    />
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
