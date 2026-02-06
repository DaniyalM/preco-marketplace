<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { CartItem } from '@/components/marketplace';
import { Price, EmptyState } from '@/components/common';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button,
    Separator,
} from '@/components/ui';
import {
    useCartQuery,
    useUpdateCartItemMutation,
    useRemoveCartItemMutation,
} from '@/composables/useCartApi';
import { useAuthStore } from '@/stores/auth';
import { computed } from 'vue';

const authStore = useAuthStore();
const { data: cartData, isLoading: cartLoading } = useCartQuery({
    enabled: computed(() => authStore.isAuthenticated),
});
const updateMutation = useUpdateCartItemMutation();
const removeMutation = useRemoveCartItemMutation();

const cartItems = computed(() => cartData.value?.items ?? []);
const cartSubtotal = computed(() => cartData.value?.subtotal ?? 0);
const cartItemCount = computed(() => cartData.value?.item_count ?? 0);
const cartEmpty = computed(() => cartItems.value.length === 0);

const updateQuantity = async (itemId: number, quantity: number) => {
    await updateMutation.mutateAsync({ itemId, quantity });
};

const removeItem = async (itemId: number) => {
    await removeMutation.mutateAsync(itemId);
};
</script>

<template>
    <AppLayout title="Shopping Cart">
        <Head title="Shopping Cart" />

        <div class="container mx-auto px-4 py-8">
            <h1 class="mb-8 text-3xl font-bold">Shopping Cart</h1>

            <!-- Empty Cart -->
            <EmptyState
                v-if="!cartLoading && cartEmpty"
                icon="cart"
                title="Your cart is empty"
                description="Looks like you haven't added anything to your cart yet."
                action-label="Start Shopping"
                action-href="/products"
            />

            <!-- Cart Content -->
            <div v-else class="grid gap-8 lg:grid-cols-3">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Cart Items ({{ cartItemCount }})</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <!-- Loading -->
                            <div v-if="cartLoading" class="space-y-4">
                                <div v-for="i in 2" :key="i" class="flex gap-4 animate-pulse">
                                    <div class="h-20 w-20 rounded-lg bg-muted" />
                                    <div class="flex-1 space-y-2">
                                        <div class="h-4 w-3/4 rounded bg-muted" />
                                        <div class="h-4 w-1/2 rounded bg-muted" />
                                    </div>
                                </div>
                            </div>

                            <!-- Items -->
                            <div v-else class="divide-y">
                                <CartItem
                                    v-for="item in cartItems"
                                    :key="item.id"
                                    :item="item"
                                    @update:quantity="(q) => updateQuantity(item.id, q)"
                                    @remove="removeItem(item.id)"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Order Summary -->
                <div>
                    <Card>
                        <CardHeader>
                            <CardTitle>Order Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Subtotal</span>
                                <Price :amount="cartSubtotal" />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Shipping</span>
                                <span class="text-sm text-muted-foreground">Calculated at checkout</span>
                            </div>
                            
                            <Separator />
                            
                            <div class="flex justify-between">
                                <span class="font-semibold">Total</span>
                                <Price :amount="cartSubtotal" size="lg" />
                            </div>

                            <Link href="/checkout" class="block">
                                <Button class="w-full" size="lg" :disabled="cartEmpty">
                                    Proceed to Checkout
                                </Button>
                            </Link>

                            <Link href="/products" class="block text-center text-sm text-muted-foreground hover:text-primary">
                                Continue Shopping
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
