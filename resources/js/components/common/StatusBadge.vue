<script setup lang="ts">
import { Badge } from '@/components/ui';
import { computed } from 'vue';

interface Props {
    status: string;
    type?: 'order' | 'product' | 'vendor' | 'kyc' | 'payment';
}

const props = withDefaults(defineProps<Props>(), {
    type: 'order',
});

const statusConfig = computed(() => {
    const configs: Record<string, Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' | 'info' }>> = {
        order: {
            pending: { label: 'Pending', variant: 'warning' },
            confirmed: { label: 'Confirmed', variant: 'info' },
            processing: { label: 'Processing', variant: 'info' },
            shipped: { label: 'Shipped', variant: 'info' },
            delivered: { label: 'Delivered', variant: 'success' },
            cancelled: { label: 'Cancelled', variant: 'destructive' },
            refunded: { label: 'Refunded', variant: 'secondary' },
            partially_refunded: { label: 'Partially Refunded', variant: 'warning' },
        },
        product: {
            draft: { label: 'Draft', variant: 'secondary' },
            pending_review: { label: 'Pending Review', variant: 'warning' },
            active: { label: 'Active', variant: 'success' },
            inactive: { label: 'Inactive', variant: 'secondary' },
            rejected: { label: 'Rejected', variant: 'destructive' },
        },
        vendor: {
            pending: { label: 'Pending', variant: 'warning' },
            under_review: { label: 'Under Review', variant: 'info' },
            approved: { label: 'Approved', variant: 'success' },
            suspended: { label: 'Suspended', variant: 'destructive' },
            rejected: { label: 'Rejected', variant: 'destructive' },
        },
        kyc: {
            pending: { label: 'Pending', variant: 'warning' },
            under_review: { label: 'Under Review', variant: 'info' },
            approved: { label: 'Verified', variant: 'success' },
            rejected: { label: 'Rejected', variant: 'destructive' },
            expired: { label: 'Expired', variant: 'secondary' },
        },
        payment: {
            pending: { label: 'Pending', variant: 'warning' },
            authorized: { label: 'Authorized', variant: 'info' },
            paid: { label: 'Paid', variant: 'success' },
            partially_refunded: { label: 'Partially Refunded', variant: 'warning' },
            refunded: { label: 'Refunded', variant: 'secondary' },
            failed: { label: 'Failed', variant: 'destructive' },
        },
    };

    const typeConfig = configs[props.type] || configs.order;
    return typeConfig[props.status] || { label: props.status, variant: 'outline' as const };
});
</script>

<template>
    <Badge :variant="statusConfig.variant">
        {{ statusConfig.label }}
    </Badge>
</template>
