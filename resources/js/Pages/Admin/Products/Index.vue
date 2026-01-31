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
    <AdminLayout title="Products">
        <Head title="Products - Admin" />

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold">All Products</h1>
                <p class="text-muted-foreground">View and moderate products from all vendors</p>
            </div>

            <Card>
                <CardContent class="p-12">
                    <EmptyState
                        icon="box"
                        title="No products yet"
                        description="Products from vendors will appear here."
                    />
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
