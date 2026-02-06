<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { Price, FormField } from '@/components/common';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button,
    Separator,
} from '@/components/ui';
import { useCartQuery } from '@/composables/useCartApi';
import { useAuthStore } from '@/stores/auth';
import { computed, ref } from 'vue';

const authStore = useAuthStore();
const { data: cartData } = useCartQuery({
    enabled: computed(() => authStore.isAuthenticated),
});
const cartItems = computed(() => cartData.value?.items ?? []);
const cartSubtotal = computed(() => cartData.value?.subtotal ?? 0);

const shippingForm = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'US',
});

const step = ref(1);

const countries = [
    { value: 'US', label: 'United States' },
    { value: 'CA', label: 'Canada' },
    { value: 'GB', label: 'United Kingdom' },
];
</script>

<template>
    <AppLayout>
        <Head title="Checkout" />

        <div class="container mx-auto px-4 py-8">
            <h1 class="mb-8 text-3xl font-bold">Checkout</h1>

            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Checkout Form -->
                <div class="lg:col-span-2">
                    <!-- Shipping -->
                    <Card class="mb-6">
                        <CardHeader>
                            <CardTitle>Shipping Address</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid gap-6 md:grid-cols-2">
                                <FormField
                                    v-model="shippingForm.first_name"
                                    label="First Name"
                                    required
                                />
                                <FormField
                                    v-model="shippingForm.last_name"
                                    label="Last Name"
                                    required
                                />
                            </div>
                            
                            <div class="grid gap-6 md:grid-cols-2">
                                <FormField
                                    v-model="shippingForm.email"
                                    type="email"
                                    label="Email"
                                    required
                                />
                                <FormField
                                    v-model="shippingForm.phone"
                                    type="tel"
                                    label="Phone"
                                />
                            </div>
                            
                            <FormField
                                v-model="shippingForm.address_line_1"
                                label="Address"
                                required
                            />
                            
                            <FormField
                                v-model="shippingForm.address_line_2"
                                label="Apartment, Suite, etc."
                            />
                            
                            <div class="grid gap-6 md:grid-cols-3">
                                <FormField
                                    v-model="shippingForm.city"
                                    label="City"
                                    required
                                />
                                <FormField
                                    v-model="shippingForm.state"
                                    label="State"
                                    required
                                />
                                <FormField
                                    v-model="shippingForm.postal_code"
                                    label="Postal Code"
                                    required
                                />
                            </div>
                            
                            <FormField
                                v-model="shippingForm.country"
                                type="select"
                                label="Country"
                                :options="countries"
                                required
                            />
                        </CardContent>
                    </Card>

                    <!-- Payment -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Payment Method</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-center text-muted-foreground py-8">
                                Payment integration coming soon.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Order Summary -->
                <div>
                    <Card class="sticky top-24">
                        <CardHeader>
                            <CardTitle>Order Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Items -->
                            <div class="space-y-3">
                                <div
                                    v-for="item in cartItems"
                                    :key="item.id"
                                    class="flex items-center gap-3"
                                >
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-muted">
                                        <img
                                            v-if="item.product.primary_image_url"
                                            :src="item.product.primary_image_url"
                                            :alt="item.product.name"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div class="flex-1 text-sm">
                                        <p class="font-medium line-clamp-1">{{ item.product.name }}</p>
                                        <p class="text-muted-foreground">Qty: {{ item.quantity }}</p>
                                    </div>
                                    <Price :amount="item.subtotal" size="sm" />
                                </div>
                            </div>

                            <Separator />

                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Subtotal</span>
                                    <Price :amount="cartSubtotal" size="sm" />
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Shipping</span>
                                    <span class="text-muted-foreground">Calculated next</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Tax</span>
                                    <span class="text-muted-foreground">Calculated next</span>
                                </div>
                            </div>

                            <Separator />

                            <div class="flex justify-between">
                                <span class="font-semibold">Total</span>
                                <Price :amount="cartSubtotal" size="lg" />
                            </div>

                            <Button class="w-full" size="lg" disabled>
                                Complete Order
                            </Button>

                            <Link href="/cart" class="block text-center text-sm text-muted-foreground hover:text-primary">
                                Return to Cart
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
