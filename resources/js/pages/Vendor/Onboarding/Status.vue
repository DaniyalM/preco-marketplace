<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Button,
    Alert,
    AlertTitle,
    AlertDescription,
} from '@/components/ui';
import { StatusBadge } from '@/components/common';
import { computed } from 'vue';

interface Props {
    vendor: {
        id: number;
        business_name: string;
        status: string;
        rejection_reason?: string;
        created_at: string;
    };
    kyc?: {
        status: string;
        rejection_reason?: string;
        submitted_at?: string;
        reviewed_at?: string;
    } | null;
}

const props = defineProps<Props>();

const statusInfo = computed(() => {
    const status = props.vendor.status;
    
    switch (status) {
        case 'pending':
            return {
                icon: 'clock',
                title: 'Application Pending',
                description: 'Your vendor application is pending. Please complete the KYC verification to proceed.',
                color: 'text-yellow-600',
            };
        case 'under_review':
            return {
                icon: 'search',
                title: 'Under Review',
                description: 'Your application is being reviewed by our team. This usually takes 1-3 business days.',
                color: 'text-blue-600',
            };
        case 'approved':
            return {
                icon: 'check',
                title: 'Approved',
                description: 'Congratulations! Your vendor account has been approved. You can now start selling.',
                color: 'text-green-600',
            };
        case 'rejected':
            return {
                icon: 'x',
                title: 'Application Rejected',
                description: props.vendor.rejection_reason || 'Your application was rejected. Please review and resubmit.',
                color: 'text-red-600',
            };
        case 'suspended':
            return {
                icon: 'pause',
                title: 'Account Suspended',
                description: props.vendor.rejection_reason || 'Your account has been suspended. Please contact support.',
                color: 'text-red-600',
            };
        default:
            return {
                icon: 'info',
                title: 'Status Unknown',
                description: 'Please contact support for more information.',
                color: 'text-muted-foreground',
            };
    }
});

const canResubmit = computed(() => {
    return props.vendor.status === 'rejected' || 
           (props.kyc?.status === 'rejected' && props.vendor.status === 'pending');
});
</script>

<template>
    <AppLayout title="Application Status">
        <Head title="Vendor Application Status" />

        <div class="container mx-auto max-w-2xl px-4 py-12">
            <Card>
                <CardHeader class="text-center">
                    <!-- Status Icon -->
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full" :class="{
                        'bg-yellow-100': vendor.status === 'pending',
                        'bg-blue-100': vendor.status === 'under_review',
                        'bg-green-100': vendor.status === 'approved',
                        'bg-red-100': vendor.status === 'rejected' || vendor.status === 'suspended',
                    }">
                        <!-- Clock -->
                        <svg v-if="statusInfo.icon === 'clock'" :class="['h-8 w-8', statusInfo.color]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        
                        <!-- Search -->
                        <svg v-else-if="statusInfo.icon === 'search'" :class="['h-8 w-8', statusInfo.color]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        
                        <!-- Check -->
                        <svg v-else-if="statusInfo.icon === 'check'" :class="['h-8 w-8', statusInfo.color]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        
                        <!-- X -->
                        <svg v-else-if="statusInfo.icon === 'x'" :class="['h-8 w-8', statusInfo.color]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>

                    <CardTitle class="text-2xl">{{ statusInfo.title }}</CardTitle>
                    <CardDescription class="text-base">
                        {{ statusInfo.description }}
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Status Details -->
                    <div class="rounded-lg bg-muted p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Business Name</span>
                            <span class="font-medium">{{ vendor.business_name }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Vendor Status</span>
                            <StatusBadge :status="vendor.status" type="vendor" />
                        </div>
                        <div v-if="kyc" class="mt-2 flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">KYC Status</span>
                            <StatusBadge :status="kyc.status" type="kyc" />
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Submitted</span>
                            <span class="text-sm">{{ new Date(vendor.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>

                    <!-- Rejection Reason -->
                    <Alert v-if="vendor.status === 'rejected' && vendor.rejection_reason" variant="destructive">
                        <AlertTitle>Rejection Reason</AlertTitle>
                        <AlertDescription>
                            {{ vendor.rejection_reason }}
                        </AlertDescription>
                    </Alert>

                    <!-- KYC Rejection -->
                    <Alert v-if="kyc?.status === 'rejected' && kyc.rejection_reason" variant="destructive">
                        <AlertTitle>KYC Verification Failed</AlertTitle>
                        <AlertDescription>
                            {{ kyc.rejection_reason }}
                        </AlertDescription>
                    </Alert>

                    <!-- Actions -->
                    <div class="flex justify-center gap-4 pt-4">
                        <Link v-if="vendor.status === 'approved'" href="/vendor">
                            <Button size="lg">
                                Go to Dashboard
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Button>
                        </Link>

                        <Link v-if="canResubmit" href="/vendor/onboarding/kyc">
                            <Button size="lg">
                                Resubmit Application
                            </Button>
                        </Link>

                        <Link v-if="!kyc && vendor.status === 'pending'" href="/vendor/onboarding/kyc">
                            <Button size="lg">
                                Complete Verification
                            </Button>
                        </Link>

                        <Link href="/">
                            <Button variant="outline">
                                Return to Marketplace
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
