<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { EmptyState } from '@/components/common';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button,
    Badge,
} from '@/components/ui';
import { ref } from 'vue';

interface Address {
    id: number;
    label?: string;
    first_name: string;
    last_name: string;
    phone?: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    is_default_shipping: boolean;
    is_default_billing: boolean;
}

const addresses = ref<Address[]>([]);
const loading = ref(false);
</script>

<template>
    <AppLayout title="My Addresses">
        <Head title="My Addresses" />

        <div class="container mx-auto max-w-4xl px-4 py-8">
            <Link href="/profile" class="mb-6 inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Profile
            </Link>

            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">My Addresses</h1>
                    <p class="mt-1 text-muted-foreground">Manage your shipping and billing addresses</p>
                </div>
                <Button>
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Address
                </Button>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="!loading && addresses.length === 0"
                icon="address"
                title="No addresses saved"
                description="Add a shipping address to make checkout faster."
                action-label="Add Address"
                @action="() => {}"
            />

            <!-- Addresses List -->
            <div v-else class="grid gap-4 md:grid-cols-2">
                <Card v-for="address in addresses" :key="address.id">
                    <CardContent class="p-6">
                        <div class="mb-4 flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold">{{ address.label || 'Address' }}</span>
                                <Badge v-if="address.is_default_shipping" variant="secondary">Default</Badge>
                            </div>
                            <Button variant="ghost" size="sm">Edit</Button>
                        </div>
                        
                        <div class="text-sm text-muted-foreground">
                            <p>{{ address.first_name }} {{ address.last_name }}</p>
                            <p>{{ address.address_line_1 }}</p>
                            <p v-if="address.address_line_2">{{ address.address_line_2 }}</p>
                            <p>{{ address.city }}, {{ address.state }} {{ address.postal_code }}</p>
                            <p>{{ address.country }}</p>
                            <p v-if="address.phone">{{ address.phone }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
