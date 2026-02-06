<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { VendorCard } from '@/components/marketplace';
import { EmptyState } from '@/components/common';
import { Card, CardContent, Input, Button } from '@/components/ui';
import { useVendorsQuery } from '@/composables/useVendorsApi';
import { ref, computed } from 'vue';

const search = ref('');
const queryParams = computed(() =>
    search.value ? { search: search.value } : undefined
);

const { data: vendorsData, isLoading: loading, refetch } = useVendorsQuery(queryParams);

const vendors = computed(() =>
    Array.isArray(vendorsData.value) ? vendorsData.value : []
);
</script>

<template>
    <AppLayout title="Vendors">
        <Head title="Our Vendors" />

        <div class="container mx-auto px-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold">Our Vendors</h1>
                <p class="mt-2 text-muted-foreground">
                    Discover trusted sellers on our marketplace
                </p>
            </div>

            <!-- Search -->
            <Card class="mb-8">
                <CardContent class="p-4">
                    <div class="flex gap-4">
                        <Input
                            v-model="search"
                            placeholder="Search vendors..."
                            class="max-w-sm"
                            @keyup.enter="refetch()"
                        />
                        <Button @click="refetch()">Search</Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Loading -->
            <div v-if="loading" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="i in 6" :key="i" class="animate-pulse">
                    <div class="aspect-[2/1] rounded-lg bg-muted" />
                    <div class="mt-4 h-5 w-1/2 rounded bg-muted" />
                </div>
            </div>

            <!-- Empty -->
            <EmptyState
                v-else-if="vendors.length === 0"
                icon="store"
                title="No vendors found"
                description="Try a different search or check back later."
            />

            <!-- Grid -->
            <div
                v-else
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <VendorCard
                    v-for="vendor in vendors"
                    :key="vendor.id"
                    :vendor="vendor"
                />
            </div>
        </div>
    </AppLayout>
</template>
