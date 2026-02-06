<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { SuperAdminLayout } from '@/components/layouts';
import { Card, CardContent, CardHeader, CardTitle, Button, Input, Label } from '@/components/ui';

const form = useForm({
    name: '',
    slug: '',
    email: '',
    support_email: '',
    domain: '',
});
</script>

<template>
    <SuperAdminLayout title="Onboard marketplace">
        <Head title="Onboard marketplace" />
        <Card class="max-w-lg">
            <CardHeader>
                <CardTitle>Onboard new marketplace</CardTitle>
                <p class="text-sm text-muted-foreground">Create a marketplace (tenant). KYC can be completed next; approval provisions a separate database.</p>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="form.post('/super-admin/marketplaces')" class="space-y-4">
                    <div>
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" class="mt-1" required />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <Label for="slug">Slug (optional)</Label>
                        <Input id="slug" v-model="form.slug" class="mt-1" placeholder="auto from name" />
                        <p v-if="form.errors.slug" class="mt-1 text-sm text-destructive">{{ form.errors.slug }}</p>
                    </div>
                    <div>
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" class="mt-1" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <Label for="domain">Domain / subdomain key</Label>
                        <Input id="domain" v-model="form.domain" class="mt-1" placeholder="e.g. acme" />
                    </div>
                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing">Create</Button>
                        <a href="/super-admin/marketplaces" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2">Cancel</a>
                    </div>
                </form>
            </CardContent>
        </Card>
    </SuperAdminLayout>
</template>
