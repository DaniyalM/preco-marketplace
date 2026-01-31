<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { AdminLayout } from '@/components/layouts';
import { StatusBadge, EmptyState } from '@/components/common';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button,
    Input,
    Select,
    Badge,
} from '@/components/ui';
import { ref, watch } from 'vue';

interface Vendor {
    id: number;
    business_name: string;
    email: string;
    business_type: string;
    status: string;
    kyc_status?: string;
    is_featured: boolean;
    created_at: string;
}

interface Props {
    vendors: {
        data: Vendor[];
        links: any;
        meta: any;
    };
    stats: {
        total: number;
        pending: number;
        under_review: number;
        approved: number;
    };
    filters: {
        status?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

const statusOptions = [
    { value: 'all', label: 'All Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'under_review', label: 'Under Review' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'suspended', label: 'Suspended' },
];

const applyFilters = () => {
    router.get('/admin/vendors', {
        search: search.value || undefined,
        status: status.value !== 'all' ? status.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(status, applyFilters);

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AdminLayout title="Vendors">
        <Head title="Vendors - Admin" />

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardContent class="p-4">
                        <p class="text-sm text-muted-foreground">Total Vendors</p>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-sm text-muted-foreground">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-sm text-muted-foreground">Under Review</p>
                        <p class="text-2xl font-bold text-blue-600">{{ stats.under_review }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-sm text-muted-foreground">Approved</p>
                        <p class="text-2xl font-bold text-green-600">{{ stats.approved }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <div class="flex-1">
                            <Input
                                v-model="search"
                                placeholder="Search by name or email..."
                                class="max-w-sm"
                            />
                        </div>
                        <Select v-model="status" class="w-40">
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Vendors Table -->
            <Card>
                <CardContent class="p-0">
                    <EmptyState
                        v-if="vendors.data.length === 0"
                        icon="store"
                        title="No vendors found"
                        description="Vendors will appear here once they register."
                        class="py-12"
                    />

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Business</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Type</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">KYC</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Joined</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="vendor in vendors.data"
                                    :key="vendor.id"
                                    class="border-b last:border-0 hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium">{{ vendor.business_name }}</p>
                                                <Badge v-if="vendor.is_featured" variant="secondary">Featured</Badge>
                                            </div>
                                            <p class="text-sm text-muted-foreground">{{ vendor.email }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm capitalize">
                                        {{ vendor.business_type }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :status="vendor.status" type="vendor" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge v-if="vendor.kyc_status" :status="vendor.kyc_status" type="kyc" />
                                        <span v-else class="text-sm text-muted-foreground">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ formatDate(vendor.created_at) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link :href="`/admin/vendors/${vendor.id}`">
                                            <Button variant="ghost" size="sm">View</Button>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
