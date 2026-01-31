<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {
    amount: number;
    compareAt?: number | null;
    currency?: string;
    locale?: string;
    size?: 'sm' | 'default' | 'lg' | 'xl';
    showDiscount?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    currency: 'USD',
    locale: 'en-US',
    size: 'default',
    showDiscount: true,
});

const formatter = computed(() =>
    new Intl.NumberFormat(props.locale, {
        style: 'currency',
        currency: props.currency,
    })
);

const formattedPrice = computed(() => formatter.value.format(props.amount));
const formattedCompareAt = computed(() =>
    props.compareAt ? formatter.value.format(props.compareAt) : null
);

const hasDiscount = computed(() =>
    props.compareAt && props.compareAt > props.amount
);

const discountPercentage = computed(() => {
    if (!hasDiscount.value || !props.compareAt) return 0;
    return Math.round(100 - (props.amount / props.compareAt) * 100);
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'text-sm';
        case 'lg':
            return 'text-xl font-semibold';
        case 'xl':
            return 'text-2xl font-bold';
        default:
            return 'text-base font-medium';
    }
});
</script>

<template>
    <div :class="cn('flex items-center gap-2', $props.class)">
        <span :class="cn(sizeClasses, hasDiscount && 'text-destructive')">
            {{ formattedPrice }}
        </span>
        
        <span
            v-if="hasDiscount && formattedCompareAt"
            class="text-sm text-muted-foreground line-through"
        >
            {{ formattedCompareAt }}
        </span>
        
        <span
            v-if="hasDiscount && showDiscount"
            class="rounded-md bg-destructive/10 px-1.5 py-0.5 text-xs font-semibold text-destructive"
        >
            -{{ discountPercentage }}%
        </span>
    </div>
</template>
