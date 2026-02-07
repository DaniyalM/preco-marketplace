<script setup lang="ts">
import { AppLayout } from '@/components/layouts';
import { Card, CardContent } from '@/components/ui';
import { Link } from '@inertiajs/vue3';

interface Props {
    orderId: string;
}

defineProps<Props>();
</script>

<template>
    <AppLayout>
        <!-- <Head title="Order Details" /> -->

        <div class="container mx-auto max-w-4xl px-4 py-8">
            <Link href="/orders" class="mb-6 inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Orders
            </Link>

            <div class="mb-8">
                <h1 class="text-3xl font-bold">Order #{{ orderId }}</h1>
                <p class="mt-1 text-muted-foreground">Order details will appear here.</p>
            </div>

            <Card>
                <CardContent class="p-12 text-center">
                    <p class="text-muted-foreground">Order details coming soon.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
