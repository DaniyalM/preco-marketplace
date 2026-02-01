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
    Alert,
    AlertTitle,
    AlertDescription,
    Separator,
} from '@/components/ui';
import { FormField, ImageUpload } from '@/components/common';
import { ref } from 'vue';

interface Props {
    vendor: {
        id: number;
        business_name: string;
        business_type: string;
    };
    existingKyc?: {
        status: string;
        rejection_reason?: string;
    } | null;
}

const props = defineProps<Props>();

const form = useForm({
    legal_name: '',
    tax_id: '',
    date_of_birth: '',
    nationality: 'US',
    id_type: 'passport',
    id_document_front: null as File | null,
    id_document_back: null as File | null,
    proof_of_address: null as File | null,
    business_registration: null as File | null,
    bank_name: '',
    bank_account_name: '',
    bank_account_number: '',
    bank_routing_number: '',
});

const idTypes = [
    { value: 'passport', label: 'Passport' },
    { value: 'national_id', label: 'National ID Card' },
    { value: 'drivers_license', label: "Driver's License" },
    { value: 'business_license', label: 'Business License' },
];

const countries = [
    { value: 'US', label: 'United States' },
    { value: 'CA', label: 'Canada' },
    { value: 'GB', label: 'United Kingdom' },
    { value: 'AU', label: 'Australia' },
    { value: 'DE', label: 'Germany' },
    { value: 'FR', label: 'France' },
];

const handleFileUpload = (field: string, file: File) => {
    (form as any)[field] = file;
};

const submit = () => {
    form.post('/vendor/onboarding/kyc', {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout title="Identity Verification">
        <Head title="KYC Verification - Vendor Onboarding" />

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
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="h-0.5 w-12 bg-primary" />
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground">
                        3
                    </div>
                </div>
                <div class="mt-2 flex justify-center gap-8 text-sm">
                    <span class="text-muted-foreground">Business Info</span>
                    <span class="text-muted-foreground">Address</span>
                    <span class="font-medium text-primary">Verification</span>
                </div>
            </div>

            <!-- Rejection Alert -->
            <Alert v-if="existingKyc?.status === 'rejected'" variant="destructive" class="mb-6">
                <AlertTitle>Previous submission rejected</AlertTitle>
                <AlertDescription>
                    {{ existingKyc.rejection_reason || 'Please review and resubmit your documents.' }}
                </AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <CardTitle>Identity Verification (KYC)</CardTitle>
                    <CardDescription>
                        To protect our marketplace, we require identity verification. Your information is encrypted and secure.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submit" class="space-y-8">
                        <!-- Personal Information -->
                        <div>
                            <h3 class="mb-4 text-lg font-medium">Personal Information</h3>
                            <div class="space-y-6">
                                <FormField
                                    v-model="form.legal_name"
                                    label="Legal Full Name"
                                    placeholder="As shown on your ID document"
                                    :error="form.errors.legal_name"
                                    required
                                />

                                <div class="grid gap-6 md:grid-cols-2">
                                    <FormField
                                        v-model="form.date_of_birth"
                                        type="date"
                                        label="Date of Birth"
                                        :error="form.errors.date_of_birth"
                                    />

                                    <FormField
                                        v-model="form.nationality"
                                        type="select"
                                        label="Nationality"
                                        :options="countries"
                                        :error="form.errors.nationality"
                                    />
                                </div>

                                <FormField
                                    v-model="form.tax_id"
                                    label="Tax ID (SSN/EIN/VAT)"
                                    placeholder="For tax reporting purposes"
                                    :error="form.errors.tax_id"
                                    hint="This is encrypted and only used for legal compliance."
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Identity Documents -->
                        <div>
                            <h3 class="mb-4 text-lg font-medium">Identity Documents</h3>
                            <div class="space-y-6">
                                <FormField
                                    v-model="form.id_type"
                                    type="select"
                                    label="Document Type"
                                    :options="idTypes"
                                    :error="form.errors.id_type"
                                    required
                                />

                                <div class="grid gap-6 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium">
                                            ID Front <span class="text-destructive">*</span>
                                        </label>
                                        <ImageUpload
                                            aspect-ratio="video"
                                            placeholder="Upload front of ID"
                                            @file="(f) => handleFileUpload('id_document_front', f)"
                                        />
                                        <p v-if="form.errors.id_document_front" class="mt-1 text-sm text-destructive">
                                            {{ form.errors.id_document_front }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium">
                                            ID Back (if applicable)
                                        </label>
                                        <ImageUpload
                                            aspect-ratio="video"
                                            placeholder="Upload back of ID"
                                            @file="(f) => handleFileUpload('id_document_back', f)"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">
                                        Proof of Address
                                    </label>
                                    <ImageUpload
                                        aspect-ratio="video"
                                        placeholder="Upload utility bill or bank statement (last 3 months)"
                                        @file="(f) => handleFileUpload('proof_of_address', f)"
                                    />
                                    <p class="mt-1 text-sm text-muted-foreground">
                                        Utility bill, bank statement, or official letter dated within the last 3 months.
                                    </p>
                                </div>

                                <div v-if="vendor.business_type !== 'individual'">
                                    <label class="mb-2 block text-sm font-medium">
                                        Business Registration Document
                                    </label>
                                    <ImageUpload
                                        aspect-ratio="video"
                                        placeholder="Upload business registration certificate"
                                        @file="(f) => handleFileUpload('business_registration', f)"
                                    />
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <!-- Bank Details -->
                        <div>
                            <h3 class="mb-4 text-lg font-medium">Bank Details (For Payouts)</h3>
                            <p class="mb-4 text-sm text-muted-foreground">
                                Enter your bank details to receive payouts. You can update this later.
                            </p>
                            <div class="space-y-6">
                                <FormField
                                    v-model="form.bank_name"
                                    label="Bank Name"
                                    placeholder="e.g., Chase, Bank of America"
                                    :error="form.errors.bank_name"
                                />

                                <FormField
                                    v-model="form.bank_account_name"
                                    label="Account Holder Name"
                                    placeholder="Name on the bank account"
                                    :error="form.errors.bank_account_name"
                                />

                                <div class="grid gap-6 md:grid-cols-2">
                                    <FormField
                                        v-model="form.bank_account_number"
                                        label="Account Number"
                                        placeholder="••••••••1234"
                                        :error="form.errors.bank_account_number"
                                    />

                                    <FormField
                                        v-model="form.bank_routing_number"
                                        label="Routing Number"
                                        placeholder="123456789"
                                        :error="form.errors.bank_routing_number"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between pt-4">
                            <Link href="/vendor/onboarding/address">
                                <Button type="button" variant="outline">
                                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Back
                                </Button>
                            </Link>

                            <Button type="submit" :loading="form.processing" size="lg">
                                Submit for Review
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
