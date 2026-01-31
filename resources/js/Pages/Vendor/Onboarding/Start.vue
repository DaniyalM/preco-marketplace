<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
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
    user: {
        name: string;
        email: string;
    };
}

defineProps<Props>();

const form = useForm({
    business_name: '',
    business_type: 'individual',
    phone: '',
    description: '',
    website: '',
});

const businessTypes = [
    { value: 'individual', label: 'Individual / Sole Proprietor' },
    { value: 'company', label: 'Company / Corporation' },
    { value: 'partnership', label: 'Partnership' },
];

const submit = () => {
    form.post('/vendor/onboarding/basic-info');
};
</script>

<template>
    <AppLayout title="Become a Vendor">
        <Head title="Vendor Onboarding" />

        <div class="container mx-auto max-w-2xl px-4 py-12">
            <!-- Progress -->
            <div class="mb-8">
                <div class="flex items-center justify-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground">
                        1
                    </div>
                    <div class="h-0.5 w-12 bg-muted" />
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground">
                        2
                    </div>
                    <div class="h-0.5 w-12 bg-muted" />
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground">
                        3
                    </div>
                </div>
                <div class="mt-2 flex justify-center gap-8 text-sm">
                    <span class="font-medium text-primary">Business Info</span>
                    <span class="text-muted-foreground">Address</span>
                    <span class="text-muted-foreground">Verification</span>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Welcome, {{ user.name }}!</CardTitle>
                    <CardDescription>
                        Let's set up your vendor account. Start by telling us about your business.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <FormField
                            v-model="form.business_name"
                            label="Business Name"
                            placeholder="Your store or business name"
                            :error="form.errors.business_name"
                            required
                        />

                        <FormField
                            v-model="form.business_type"
                            type="select"
                            label="Business Type"
                            :options="businessTypes"
                            :error="form.errors.business_type"
                            required
                        />

                        <FormField
                            v-model="form.phone"
                            type="tel"
                            label="Phone Number"
                            placeholder="+1 (555) 000-0000"
                            :error="form.errors.phone"
                            required
                        />

                        <FormField
                            v-model="form.description"
                            type="textarea"
                            label="Business Description"
                            placeholder="Tell customers about your business and what you sell..."
                            :rows="4"
                            :error="form.errors.description"
                            hint="This will be displayed on your store page."
                        />

                        <FormField
                            v-model="form.website"
                            type="url"
                            label="Website (Optional)"
                            placeholder="https://yourwebsite.com"
                            :error="form.errors.website"
                        />

                        <div class="flex justify-end pt-4">
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
