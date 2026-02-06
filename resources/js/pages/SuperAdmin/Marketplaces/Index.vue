<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { SuperAdminLayout } from '@/components/layouts';
import { Card, CardContent, CardHeader, CardTitle, Button, Badge } from '@/components/ui';

defineProps<{
    marketplaces: { data: Array<{ id: number; name: string; slug: string; status: string; has_tenant_database: boolean }> };
    stats: { pending_kyc: number; kyc_under_review: number; approved: number; rejected: number };
}>();
</script>

<template>
    <SuperAdminLayout title="Marketplaces">
        <Head title="Marketplaces" />
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Marketplaces</h1>
                <Link href="/super-admin/marketplaces/create"><Button>Onboard marketplace</Button></Link>
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                <Card><CardHeader class="pb-2"><CardTitle class="text-sm">Pending KYC</CardTitle></CardHeader><CardContent><div class="text-2xl font-bold">{{ stats.pending_kyc }}</div></CardContent></Card>
                <Card><CardHeader class="pb-2"><CardTitle class="text-sm">Under review</CardTitle></CardHeader><CardContent><div class="text-2xl font-bold">{{ stats.kyc_under_review }}</div></CardContent></Card>
                <Card><CardHeader class="pb-2"><CardTitle class="text-sm">Approved</CardTitle></CardHeader><CardContent><div class="text-2xl font-bold">{{ stats.approved }}</div></CardContent></Card>
                <Card><CardHeader class="pb-2"><CardTitle class="text-sm">Rejected</CardTitle></CardHeader><CardContent><div class="text-2xl font-bold">{{ stats.rejected }}</div></CardContent></Card>
            </div>
            <Card>
                <CardContent class="p-0">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b"><th class="p-4 text-left font-medium">Name</th><th class="p-4 text-left font-medium">Slug</th><th class="p-4 text-left font-medium">Status</th><th class="p-4 text-left font-medium">Tenant DB</th><th class="p-4 text-right font-medium">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="m in marketplaces.data" :key="m.id" class="border-b last:border-0">
                                <td class="p-4">{{ m.name }}</td><td class="p-4">{{ m.slug }}</td>
                                <td class="p-4"><Badge :variant="m.status === 'approved' ? 'default' : 'secondary'">{{ m.status }}</Badge></td>
                                <td class="p-4">{{ m.has_tenant_database ? 'Yes' : 'No' }}</td>
                                <td class="p-4 text-right"><Link :href="`/super-admin/marketplaces/${m.id}`"><Button variant="outline" size="sm">View</Button></Link></td>
                            </tr>
                            <tr v-if="!marketplaces.data?.length"><td colspan="5" class="p-8 text-center text-muted-foreground">No marketplaces yet.</td></tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    </SuperAdminLayout>
</template>
