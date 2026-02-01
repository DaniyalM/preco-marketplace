<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AdminLayout } from '@/components/layouts';
import { EmptyState } from '@/components/common';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button,
} from '@/components/ui';
</script>

<template>
    <AdminLayout title="Categories">
        <Head title="Categories - Admin" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Categories</h1>
                    <p class="text-muted-foreground">Manage product categories</p>
                </div>
                <Button>
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Category
                </Button>
            </div>

            <Card>
                <CardContent class="p-12">
                    <EmptyState
                        icon="box"
                        title="No categories yet"
                        description="Create your first category to organize products."
                        action-label="Add Category"
                        @action="() => {}"
                    />
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
