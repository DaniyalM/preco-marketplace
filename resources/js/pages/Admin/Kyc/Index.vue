<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { AdminLayout } from '@/components/layouts';
import { StatusBadge, EmptyState } from '@/components/common';
import {
    Card,
    CardContent,
    Button,
    Input,
    Combobox,
    Badge,
} from '@/components/ui';
import { ref, watch } from 'vue';

interface KycSubmission {
    id: number;
    vendor_id: number;
    vendor_name: string;
    vendor_email: string;
    legal_name: string;
    id_type: string;
    status: string;
    is_resubmission: boolean;
    submission_count: number;
    submitted_at?: string;
}

interface Props {
    submissions: {
        data: KycSubmission[];
        links: any;
        meta: any;
    };
    stats: {
        pending: number;
        under_review: number;
        approved: number;
        rejected: number;
    };
    filters: {
        status?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'action_required');

const statusOptions = [
    { value: 'action_required', label: 'Action Required' },
    { value: 'pending', label: 'Pending' },
    { value: 'under_review', label: 'Under Review' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'all', label: 'All' },
].map((o) => ({ value: o.value, label: o.label }));

const applyFilters = () => {
    router.get('/admin/kyc', {
        search: search.value || undefined,
        status: status.value,
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

const formatDate = (dateString?: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const idTypeLabels: Record<string, string> = {
    passport: 'Passport',
    national_id: 'National ID',
    drivers_license: "Driver's License",
    business_license: 'Business License',
};
</script>

<template>
    <AdminLayout title="KYC Reviews">
        <Head title="KYC Reviews - Admin" />

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-4">
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
                <Card>
                    <CardContent class="p-4">
                        <p class="text-sm text-muted-foreground">Rejected</p>
                        <p class="text-2xl font-bold text-red-600">{{ stats.rejected }}</p>
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
                                placeholder="Search by vendor name or email..."
                                class="max-w-sm"
                            />
                        </div>
                        <Combobox
                            v-model="status"
                            :options="statusOptions"
                            placeholder="Status"
                            class="w-48"
                            :searchable="true"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- KYC Submissions Table -->
            <Card>
                <CardContent class="p-0">
                    <EmptyState
                        v-if="submissions.data.length === 0"
                        icon="search"
                        title="No KYC submissions found"
                        :description="status === 'action_required' ? 'All caught up! No pending reviews.' : 'No submissions match your filters.'"
                        class="py-12"
                    />

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Vendor</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Legal Name</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">ID Type</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Submitted</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="kyc in submissions.data"
                                    :key="kyc.id"
                                    class="border-b last:border-0 hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium">{{ kyc.vendor_name }}</p>
                                                <Badge v-if="kyc.is_resubmission" variant="warning">
                                                    Resubmission #{{ kyc.submission_count }}
                                                </Badge>
                                            </div>
                                            <p class="text-sm text-muted-foreground">{{ kyc.vendor_email }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ kyc.legal_name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ idTypeLabels[kyc.id_type] || kyc.id_type }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :status="kyc.status" type="kyc" />
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ formatDate(kyc.submitted_at) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link :href="`/admin/kyc/${kyc.id}`">
                                            <Button variant="ghost" size="sm">Review</Button>
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
