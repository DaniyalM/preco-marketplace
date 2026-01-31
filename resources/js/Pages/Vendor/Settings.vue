<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { VendorLayout } from '@/components/layouts';
import { FormField, ImageUpload } from '@/components/common';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Button,
    Separator,
} from '@/components/ui';
import { ref, computed } from 'vue';

const page = usePage();
const vendor = computed(() => page.props.auth?.vendor);

const form = ref({
    business_name: vendor.value?.business_name || '',
    phone: '',
    description: '',
    website: '',
});
</script>

<template>
    <VendorLayout title="Settings">
        <Head title="Settings - Vendor Dashboard" />

        <div class="max-w-3xl space-y-6">
            <!-- Business Information -->
            <Card>
                <CardHeader>
                    <CardTitle>Business Information</CardTitle>
                    <CardDescription>Update your store details</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <FormField
                        v-model="form.business_name"
                        label="Business Name"
                        disabled
                        hint="Contact support to change your business name"
                    />

                    <FormField
                        v-model="form.phone"
                        type="tel"
                        label="Phone Number"
                        placeholder="+1 (555) 000-0000"
                    />

                    <FormField
                        v-model="form.description"
                        type="textarea"
                        label="Business Description"
                        placeholder="Tell customers about your business..."
                        :rows="4"
                    />

                    <FormField
                        v-model="form.website"
                        type="url"
                        label="Website"
                        placeholder="https://yourwebsite.com"
                    />

                    <Button>Save Changes</Button>
                </CardContent>
            </Card>

            <!-- Store Appearance -->
            <Card>
                <CardHeader>
                    <CardTitle>Store Appearance</CardTitle>
                    <CardDescription>Customize how your store looks</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium">Store Logo</label>
                            <ImageUpload
                                aspect-ratio="square"
                                placeholder="Upload logo"
                            />
                            <p class="mt-1 text-sm text-muted-foreground">
                                Recommended: 400x400px, PNG or JPG
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium">Store Banner</label>
                            <ImageUpload
                                aspect-ratio="banner"
                                placeholder="Upload banner"
                            />
                            <p class="mt-1 text-sm text-muted-foreground">
                                Recommended: 1200x400px, PNG or JPG
                            </p>
                        </div>
                    </div>

                    <Button>Save Appearance</Button>
                </CardContent>
            </Card>

            <!-- Notifications -->
            <Card>
                <CardHeader>
                    <CardTitle>Notifications</CardTitle>
                    <CardDescription>Manage email notifications</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">New Orders</p>
                                <p class="text-sm text-muted-foreground">Get notified when you receive a new order</p>
                            </div>
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300" checked />
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">Low Stock Alerts</p>
                                <p class="text-sm text-muted-foreground">Get notified when products are running low</p>
                            </div>
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300" checked />
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">Product Reviews</p>
                                <p class="text-sm text-muted-foreground">Get notified when customers review your products</p>
                            </div>
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </VendorLayout>
</template>
