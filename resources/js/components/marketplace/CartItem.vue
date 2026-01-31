<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Button, Badge } from '@/components/ui';
import { Price } from '@/components/common';
import { Link } from '@inertiajs/vue3';
import { computed, type HTMLAttributes } from 'vue';

export interface CartItemData {
    id: number;
    quantity: number;
    product: {
        id: number;
        name: string;
        slug: string;
        primary_image_url?: string | null;
    };
    variant?: {
        id: number;
        display_name: string;
        option_values: Record<string, string>;
    } | null;
    price: number;
    compare_at_price?: number | null;
    is_in_stock: boolean;
    available_stock: number;
}

interface Props extends /* @vue-ignore */ HTMLAttributes {
    item: CartItemData;
    readonly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    readonly: false,
});

const emit = defineEmits<{
    'update:quantity': [quantity: number];
    remove: [];
}>();

const productUrl = computed(() => `/products/${props.item.product.slug}`);

const subtotal = computed(() => props.item.price * props.item.quantity);

const canIncrement = computed(() => 
    props.item.quantity < props.item.available_stock
);
</script>

<template>
    <div :class="cn('flex gap-4 py-4', $props.class)">
        <!-- Image -->
        <Link :href="productUrl" class="shrink-0">
            <div class="h-20 w-20 overflow-hidden rounded-lg bg-muted">
                <img
                    v-if="item.product.primary_image_url"
                    :src="item.product.primary_image_url"
                    :alt="item.product.name"
                    class="h-full w-full object-cover"
                />
            </div>
        </Link>
        
        <!-- Details -->
        <div class="flex flex-1 flex-col">
            <div class="flex justify-between gap-4">
                <div>
                    <Link :href="productUrl">
                        <h3 class="font-medium transition-colors hover:text-primary">
                            {{ item.product.name }}
                        </h3>
                    </Link>
                    
                    <!-- Variant Options -->
                    <p v-if="item.variant" class="mt-1 text-sm text-muted-foreground">
                        {{ item.variant.display_name }}
                    </p>
                    
                    <!-- Stock Warning -->
                    <Badge v-if="!item.is_in_stock" variant="destructive" class="mt-1">
                        Out of Stock
                    </Badge>
                    <Badge
                        v-else-if="item.quantity > item.available_stock"
                        variant="warning"
                        class="mt-1"
                    >
                        Only {{ item.available_stock }} available
                    </Badge>
                </div>
                
                <!-- Price -->
                <div class="text-right">
                    <Price
                        :amount="subtotal"
                        :compare-at="item.compare_at_price ? item.compare_at_price * item.quantity : null"
                    />
                    <p v-if="item.quantity > 1" class="text-sm text-muted-foreground">
                        {{ item.quantity }} x ${{ item.price.toFixed(2) }}
                    </p>
                </div>
            </div>
            
            <!-- Quantity Controls -->
            <div v-if="!readonly" class="mt-auto flex items-center justify-between pt-2">
                <div class="flex items-center gap-2">
                    <Button
                        size="icon"
                        variant="outline"
                        class="h-8 w-8"
                        :disabled="item.quantity <= 1"
                        @click="emit('update:quantity', item.quantity - 1)"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                    </Button>
                    
                    <span class="w-8 text-center font-medium">{{ item.quantity }}</span>
                    
                    <Button
                        size="icon"
                        variant="outline"
                        class="h-8 w-8"
                        :disabled="!canIncrement"
                        @click="emit('update:quantity', item.quantity + 1)"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </Button>
                </div>
                
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-muted-foreground hover:text-destructive"
                    @click="emit('remove')"
                >
                    Remove
                </Button>
            </div>
        </div>
    </div>
</template>
