<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { SuperAdminLayout } from '@/components/layouts';
import { Card, CardContent, CardHeader, CardTitle, Button, Label, Textarea } from '@/components/ui';

interface Props {
    marketplace: { id: number; name: string; slug: string; status: string };
    kyc: {
        id: number;
        legal_name: string;
        tax_id: string | null;
        business_type: string | null;
        id_type: string;
        status: string;
        submitted_at: string | null;
        reviewed_at: string | null;
        rejection_reason: string | null;
        admin_notes: string | null;
        is_resubmission: boolean;
        submission_count: number;
        documents: { id_front: string | null; id_back: string | null; proof_of_address: string | null; business_registration: string | null };
    };
}

defineProps<Props>();

const approveForm = useForm({ notes: '' });
const rejectForm = useForm({ reason: '', notes: '' });
</script>

<template>
    <SuperAdminLayout title="KYC Review">
        <Head title="Marketplace KYC" />
        <div class="space-y-6">
            <Link :href="`/super-admin/marketplaces/${marketplace.id}`" class="text-sm text-muted-foreground hover:text-foreground">&larr; {{ marketplace.name }}</Link>
            <h1 class="text-2xl font-semibold">KYC: {{ kyc.legal_name }}</h1>
            <Card>
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <p><span class="text-muted-foreground">Status:</span> {{ kyc.status }}</p>
                    <p><span class="text-muted-foreground">ID type:</span> {{ kyc.id_type }}</p>
                    <p v-if="kyc.submitted_at"><span class="text-muted-foreground">Submitted:</span> {{ kyc.submitted_at }}</p>
                    <p v-if="kyc.rejection_reason"><span class="text-muted-foreground">Rejection reason:</span> {{ kyc.rejection_reason }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Documents</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <p v-if="kyc.documents.id_front"><a :href="kyc.documents.id_front" target="_blank" rel="noopener" class="text-primary underline">ID front</a></p>
                    <p v-if="kyc.documents.id_back"><a :href="kyc.documents.id_back" target="_blank" rel="noopener" class="text-primary underline">ID back</a></p>
                    <p v-if="kyc.documents.proof_of_address"><a :href="kyc.documents.proof_of_address" target="_blank" rel="noopener" class="text-primary underline">Proof of address</a></p>
                    <p v-if="kyc.documents.business_registration"><a :href="kyc.documents.business_registration" target="_blank" rel="noopener" class="text-primary underline">Business registration</a></p>
                    <p v-if="!kyc.documents.id_front && !kyc.documents.id_back" class="text-muted-foreground">No documents uploaded.</p>
                </CardContent>
            </Card>
            <Card v-if="kyc.status === 'pending' || kyc.status === 'under_review'">
                <CardHeader>
                    <CardTitle>Actions</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <form v-if="kyc.status === 'pending'" @submit.prevent="() => rejectForm.post(`/super-admin/marketplaces/${marketplace.id}/kyc/start-review`)">
                        <Button type="submit" variant="outline">Start review</Button>
                    </form>
                    <form class="space-y-4" @submit.prevent="approveForm.post(`/super-admin/marketplaces/${marketplace.id}/kyc/approve`)">
                        <div>
                            <Label for="notes">Notes (optional)</Label>
                            <Textarea id="notes" v-model="approveForm.notes" class="mt-1" rows="2" />
                        </div>
                        <Button type="submit" :disabled="approveForm.processing">Approve & provision tenant DB</Button>
                    </form>
                    <form class="space-y-4 border-t pt-4" @submit.prevent="rejectForm.post(`/super-admin/marketplaces/${marketplace.id}/kyc/reject`)">
                        <div>
                            <Label for="reason">Rejection reason (required)</Label>
                            <Textarea id="reason" v-model="rejectForm.reason" class="mt-1" rows="2" required />
                            <p v-if="rejectForm.errors.reason" class="mt-1 text-sm text-destructive">{{ rejectForm.errors.reason }}</p>
                        </div>
                        <div>
                            <Label for="reject_notes">Notes</Label>
                            <Textarea id="reject_notes" v-model="rejectForm.notes" class="mt-1" rows="2" />
                        </div>
                        <Button type="submit" variant="destructive" :disabled="rejectForm.processing">Reject KYC</Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </SuperAdminLayout>
</template>
