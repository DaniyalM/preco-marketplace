<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { Price, FormField } from '@/components/common';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button,
    Separator,
    Input,
} from '@/components/ui';
import { useCartQuery } from '@/composables/useCartApi';
import {
    useBlockchainNetworksQuery,
    usePlaceOrderMutation,
    useConfirmCryptoPaymentMutation,
} from '@/composables/useCheckoutApi';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import type { OrderData } from '@/api/checkout';
import { computed, ref, watch } from 'vue';

const authStore = useAuthStore();
const toast = useToastStore();
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

const paymentMethod = ref<'blockchain' | 'card'>('blockchain');
const paymentNetwork = ref('');
const placedOrder = ref<OrderData | null>(null);
const txHash = ref('');
const payerWalletAddress = ref('');

const { data: networksData } = useBlockchainNetworksQuery();
const networks = computed(() => networksData.value ?? []);
// Keep selected network in sync with available list
watch(networks, (n) => {
    if (n.length && !n.some((x) => x.key === paymentNetwork.value)) {
        paymentNetwork.value = n[0].key;
    } else if (n.length && !paymentNetwork.value) {
        paymentNetwork.value = n[0].key;
    }
}, { immediate: true });

const placeOrderMutation = usePlaceOrderMutation();
const confirmCryptoMutation = useConfirmCryptoPaymentMutation();

const canPlaceOrder = computed(() => {
    const s = shippingForm.value;
    const shippingFilled =
        s.first_name?.trim() &&
        s.last_name?.trim() &&
        s.email?.trim() &&
        s.address_line_1?.trim() &&
        s.city?.trim() &&
        s.postal_code?.trim() &&
        s.country;
    if (!shippingFilled) return false;
    // Card is not implemented yet
    if (paymentMethod.value === 'card') return false;
    // Blockchain: backend uses default network if none selected
    return true;
});

const blockchainPayment = computed(() => placedOrder.value?.blockchain_payment ?? null);

function buildShippingAddress() {
    const s = shippingForm.value;
    return {
        first_name: s.first_name,
        last_name: s.last_name,
        email: s.email,
        phone: s.phone || undefined,
        address_line_1: s.address_line_1,
        address_line_2: s.address_line_2 || undefined,
        city: s.city,
        state: s.state || undefined,
        postal_code: s.postal_code,
        country: s.country,
    };
}

async function handlePlaceOrder() {
    if (!canPlaceOrder.value || placeOrderMutation.isPending.value) return;
    if (paymentMethod.value === 'card') {
        toast.error('Card payment is coming soon. Use decentralized (crypto) payment.');
        return;
    }
    try {
        const order = await placeOrderMutation.mutateAsync({
            shipping_address: buildShippingAddress(),
            payment_method: 'blockchain',
            payment_network: paymentNetwork.value || undefined,
        });
        placedOrder.value = order;
        toast.success('Order created. Complete payment below.');
    } catch (err: unknown) {
        const msg = err && typeof err === 'object' && 'response' in err
            ? (err as { response?: { data?: { error?: string } } }).response?.data?.error
            : 'Failed to create order';
        toast.error(typeof msg === 'string' ? msg : 'Failed to create order');
    }
}

async function handleConfirmPayment() {
    if (!placedOrder.value?.id || !txHash.value.trim() || !payerWalletAddress.value.trim()) {
        toast.error('Please enter transaction hash and your wallet address.');
        return;
    }
    try {
        await confirmCryptoMutation.mutateAsync({
            orderId: placedOrder.value.id,
            txHash: txHash.value.trim(),
            payerWalletAddress: payerWalletAddress.value.trim(),
        });
        toast.success('Payment confirmed. Thank you!');
        router.visit('/orders');
    } catch (err: unknown) {
        const msg = err && typeof err === 'object' && 'response' in err
            ? (err as { response?: { data?: { error?: string } } }).response?.data?.error
            : 'Failed to confirm payment';
        toast.error(typeof msg === 'string' ? msg : 'Failed to confirm payment');
    }
}

function copyAddress() {
    const addr = blockchainPayment.value?.merchant_wallet_address;
    if (!addr) return;
    navigator.clipboard.writeText(addr).then(() => toast.success('Address copied'));
}

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
                <div class="lg:col-span-2">
                    <!-- Shipping -->
                    <Card class="mb-6">
                        <CardHeader>
                            <CardTitle>Shipping Address</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid gap-6 md:grid-cols-2">
                                <FormField v-model="shippingForm.first_name" label="First Name" required />
                                <FormField v-model="shippingForm.last_name" label="Last Name" required />
                            </div>
                            <div class="grid gap-6 md:grid-cols-2">
                                <FormField v-model="shippingForm.email" type="email" label="Email" required />
                                <FormField v-model="shippingForm.phone" type="tel" label="Phone" />
                            </div>
                            <FormField v-model="shippingForm.address_line_1" label="Address" required />
                            <FormField v-model="shippingForm.address_line_2" label="Apartment, Suite, etc." />
                            <div class="grid gap-6 md:grid-cols-3">
                                <FormField v-model="shippingForm.city" label="City" required />
                                <FormField v-model="shippingForm.state" label="State" />
                                <FormField v-model="shippingForm.postal_code" label="Postal Code" required />
                            </div>
                            <FormField v-model="shippingForm.country" type="select" label="Country" :options="countries" required />
                        </CardContent>
                    </Card>

                    <!-- Payment Method -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Payment Method</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    class="flex-1 rounded-xl border-2 px-4 py-3 text-left transition"
                                    :class="paymentMethod === 'blockchain'
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-border hover:border-muted-foreground/30'"
                                    @click="paymentMethod = 'blockchain'"
                                >
                                    <span class="font-semibold">Decentralized (Crypto)</span>
                                    <p class="mt-1 text-sm text-muted-foreground">
                                        Pay with ETH, MATIC on Ethereum or Polygon. No intermediary.
                                    </p>
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded-xl border-2 border-border px-4 py-3 text-left opacity-75"
                                    @click="paymentMethod = 'card'"
                                >
                                    <span class="font-semibold">Card</span>
                                    <p class="mt-1 text-sm text-muted-foreground">Coming soon</p>
                                </button>
                            </div>

                            <div v-if="paymentMethod === 'blockchain' && networks.length" class="rounded-lg bg-muted/50 p-4">
                                <label class="mb-2 block text-sm font-medium">Network</label>
                                <select
                                    v-model="paymentNetwork"
                                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
                                >
                                    <option v-for="n in networks" :key="n.key" :value="n.key">
                                        {{ n.name }} ({{ n.native_currency }})
                                    </option>
                                </select>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- After order placed: payment instructions + confirm form -->
                    <Card v-if="placedOrder && blockchainPayment" class="mt-6 border-primary/20">
                        <CardHeader>
                            <CardTitle>Complete your payment</CardTitle>
                            <p class="text-sm text-muted-foreground">
                                Send the exact amount below to the merchant wallet. Then paste the transaction hash to confirm.
                            </p>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="rounded-xl bg-muted/50 p-4">
                                <p class="text-sm text-muted-foreground">Amount</p>
                                <p class="text-2xl font-bold">
                                    {{ blockchainPayment.amount_crypto }} {{ blockchainPayment.currency }}
                                </p>
                                <p class="text-sm text-muted-foreground">≈ ${{ blockchainPayment.amount_usd.toFixed(2) }} USD</p>
                            </div>
                            <div>
                                <p class="mb-2 text-sm text-muted-foreground">Merchant wallet address</p>
                                <div class="flex gap-2">
                                    <Input
                                        :model-value="blockchainPayment.merchant_wallet_address ?? ''"
                                        readonly
                                        class="font-mono text-sm"
                                    />
                                    <Button variant="outline" size="icon" class="shrink-0" @click="copyAddress">
                                        Copy
                                    </Button>
                                </div>
                            </div>
                            <Separator />
                            <p class="text-sm font-medium">I've sent the payment</p>
                            <div class="space-y-3">
                                <Input
                                    v-model="txHash"
                                    placeholder="Transaction hash (0x...)"
                                    class="font-mono text-sm"
                                />
                                <Input
                                    v-model="payerWalletAddress"
                                    placeholder="Your wallet address (0x...)"
                                    class="font-mono text-sm"
                                />
                                <Button
                                    class="w-full"
                                    :disabled="!txHash.trim() || !payerWalletAddress.trim() || confirmCryptoMutation.isPending.value"
                                    @click="handleConfirmPayment"
                                >
                                    {{ confirmCryptoMutation.isPending.value ? 'Confirming…' : 'Confirm payment' }}
                                </Button>
                            </div>
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
                            <Button
                                v-if="!placedOrder"
                                class="w-full"
                                size="lg"
                                :disabled="!canPlaceOrder || placeOrderMutation.isPending.value || (paymentMethod === 'card')"
                                @click="handlePlaceOrder"
                            >
                                {{ placeOrderMutation.isPending.value ? 'Creating order…' : 'Place order' }}
                            </Button>
                            <p v-else class="text-center text-sm text-muted-foreground">
                                Complete payment in the form on the left.
                            </p>
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
