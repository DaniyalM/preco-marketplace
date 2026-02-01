<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { VendorCard } from '@/components/marketplace';
import { EmptyState } from '@/components/common';
import { Card, CardContent, Input, Button } from '@/components/ui';
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Vendor {
    id: number;
    business_name: string;
    slug: string;
    logo?: string | null;
    banner?: string | null;
    description?: string | null;
    is_featured?: boolean;
    products_count?: number;
}

const vendors = ref<Vendor[]>([]);
const loading = ref(true);
const search = ref('');

const fetchVendors = async () => {
    loading.value = true;
    try {
        const params: Record<string, string> = {};
        if (search.value) params.search = search.value;
        
        const response = await axios.get('/api/public/vendors', { params });
        vendors.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch vendors', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchVendors);
</script>

<template>
    <AppLayout title="Vendors">
        <Head title="Our Vendors" />

        <div class="container mx-auto px-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold">Our Vendors</h1>
                <p class="mt-2 text-muted-foreground">Discover trusted sellers on our marketplace</p>
            </div>

            <!-- Search -->
            <Card class="mb-8">
                <CardContent class="p-4">
                    <div class="flex gap-4">
                        <Input
                            v-model="search"
                            placeholder="Search vendors..."
                            class="max-w-sm"
                            @keyup.enter="fetchVendors"
                        />
                        <Button @click="fetchVendors">Search</Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Loading State -->
            <div v-if="loading" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="i in 6" :key="i" class="animate-pulse">
                    <div class="h-24 rounded-t-lg bg-muted" />
                    <div class="rounded-b-lg border border-t-0 p-4">
                        <div class="h-4 w-3/4 rounded bg-muted" />
                        <div class="mt-2 h-3 w-1/2 rounded bg-muted" />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-else-if="vendors.length === 0"
                icon="store"
                title="No vendors found"
                :description="search ? 'Try a different search term.' : 'Vendors will appear here once they join.'"
            />

            <!-- Vendors Grid -->
            <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <VendorCard
                    v-for="vendor in vendors"
                    :key="vendor.id"
                    :vendor="vendor"
                />
            </div>
        </div>
    </AppLayout>
</template>
