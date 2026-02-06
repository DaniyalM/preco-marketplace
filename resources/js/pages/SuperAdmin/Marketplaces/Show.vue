<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { SuperAdminLayout } from '@/components/layouts';
import { Card, CardContent, CardHeader, CardTitle, Button, Badge } from '@/components/ui';

interface Props {
    marketplace: {
        id: number;
        name: string;
        slug: string;
        domain: string | null;
        email: string | null;
        support_email: string | null;
        status: string;
        has_tenant_database: boolean;
        approved_at: string | null;
        rejected_at: string | null;
        rejection_reason: string | null;
        created_at: string;
        kyc: { id: number; legal_name: string; status: string; submitted_at: string | null; reviewed_at: string | null; rejection_reason: string | null } | null;
    };
}

defineProps<Props>();
</script>

<template>
    <SuperAdminLayout title="Marketplace">
        <Head :title="marketplace.name" />
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/super-admin/marketplaces" class="text-sm text-muted-foreground hover:text-foreground">&larr; Marketplaces</Link>
                    <h1 class="mt-1 text-2xl font-semibold">{{ marketplace.name }}</h1>
                </div>
                <Badge :variant="marketplace.status === 'approved' ? 'default' : 'secondary'">{{ marketplace.status }}</Badge>
            </div>
            <Card>
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <p><span class="text-muted-foreground">Slug:</span> {{ marketplace.slug }}</p>
                    <p><span class="text-muted-foreground">Domain:</span> {{ marketplace.domain ?? '—' }}</p>
                    <p><span class="text-muted-foreground">Email:</span> {{ marketplace.email ?? '—' }}</p>
                    <p><span class="text-muted-foreground">Tenant DB:</span> {{ marketplace.has_tenant_database ? 'Provisioned' : 'Not provisioned' }}</p>
                    <p v-if="marketplace.rejection_reason"><span class="text-muted-foreground">Rejection reason:</span> {{ marketplace.rejection_reason }}</p>
                </CardContent>
            </Card>
            <Card v-if="marketplace.kyc">
                <CardHeader>
                    <CardTitle>KYC</CardTitle>
                    <p class="text-sm text-muted-foreground">Status: {{ marketplace.kyc.status }}</p>
                </CardHeader>
                <CardContent>
                    <Link :href="`/super-admin/marketplaces/${marketplace.id}/kyc`">
                        <Button variant="outline">View / Review KYC</Button>
                    </Link>
                </CardContent>
            </Card>
        </div>
    </SuperAdminLayout>
</template>
