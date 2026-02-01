<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Button,
} from '@/components/ui';
import { FormField } from '@/components/common';

interface Props {
    vendor: {
        id: number;
        business_name: string;
        address_line_1?: string;
        city?: string;
        state?: string;
        postal_code?: string;
        country?: string;
    };
}

const props = defineProps<Props>();

const form = useForm({
    address_line_1: props.vendor.address_line_1 || '',
    address_line_2: '',
    city: props.vendor.city || '',
    state: props.vendor.state || '',
    postal_code: props.vendor.postal_code || '',
    country: props.vendor.country || 'US',
});

const countries = [
    { value: 'US', label: 'United States' },
    { value: 'CA', label: 'Canada' },
    { value: 'GB', label: 'United Kingdom' },
    { value: 'AU', label: 'Australia' },
    { value: 'DE', label: 'Germany' },
    { value: 'FR', label: 'France' },
];

const submit = () => {
    form.post('/vendor/onboarding/address');
};
</script>

<template>
    <AppLayout title="Business Address">
        <Head title="Business Address - Vendor Onboarding" />

        <div class="container mx-auto max-w-2xl px-4 py-12">
            <!-- Progress -->
            <div class="mb-8">
                <div class="flex items-center justify-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="h-0.5 w-12 bg-primary" />
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground">
                        2
                    </div>
                    <div class="h-0.5 w-12 bg-muted" />
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground">
                        3
                    </div>
                </div>
                <div class="mt-2 flex justify-center gap-8 text-sm">
                    <span class="text-muted-foreground">Business Info</span>
                    <span class="font-medium text-primary">Address</span>
                    <span class="text-muted-foreground">Verification</span>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Business Address</CardTitle>
                    <CardDescription>
                        Enter your business address. This is used for shipping and legal purposes.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <FormField
                            v-model="form.address_line_1"
                            label="Street Address"
                            placeholder="123 Main Street"
                            :error="form.errors.address_line_1"
                            required
                        />

                        <FormField
                            v-model="form.address_line_2"
                            label="Apartment, Suite, etc. (Optional)"
                            placeholder="Suite 100"
                            :error="form.errors.address_line_2"
                        />

                        <div class="grid gap-6 md:grid-cols-2">
                            <FormField
                                v-model="form.city"
                                label="City"
                                placeholder="New York"
                                :error="form.errors.city"
                                required
                            />

                            <FormField
                                v-model="form.state"
                                label="State / Province"
                                placeholder="NY"
                                :error="form.errors.state"
                                required
                            />
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <FormField
                                v-model="form.postal_code"
                                label="Postal Code"
                                placeholder="10001"
                                :error="form.errors.postal_code"
                                required
                            />

                            <FormField
                                v-model="form.country"
                                type="select"
                                label="Country"
                                :options="countries"
                                :error="form.errors.country"
                                required
                            />
                        </div>

                        <div class="flex justify-between pt-4">
                            <Link href="/vendor/onboarding">
                                <Button type="button" variant="outline">
                                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Back
                                </Button>
                            </Link>

                            <Button type="submit" :loading="form.processing" size="lg">
                                Continue
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
