<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {
    value?: number | string | null;
    max?: number;
    showValue?: boolean;
    reviewCount?: number;
    size?: 'sm' | 'default' | 'lg';
    interactive?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    max: 5,
    showValue: false,
    size: 'default',
    interactive: false,
});

const emit = defineEmits<{
    'update:value': [value: number];
}>();

const numericValue = computed(() => {
    const v = props.value;
    if (v == null || v === '') return 0;
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
});

const stars = computed(() => {
    const value = numericValue.value;
    const result = [];
    for (let i = 1; i <= props.max; i++) {
        if (i <= Math.floor(value)) {
            result.push('full');
        } else if (i - 0.5 <= value) {
            result.push('half');
        } else {
            result.push('empty');
        }
    }
    return result;
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-3 w-3';
        case 'lg':
            return 'h-6 w-6';
        default:
            return 'h-4 w-4';
    }
});

const handleClick = (index: number) => {
    if (props.interactive) {
        emit('update:value', index);
    }
};
</script>

<template>
    <div :class="cn('flex items-center gap-1', $props.class)">
        <div class="flex items-center">
            <button
                v-for="(type, index) in stars"
                :key="index"
                type="button"
                :class="[
                    sizeClasses,
                    interactive ? 'cursor-pointer hover:scale-110 transition-transform' : 'cursor-default',
                ]"
                :disabled="!interactive"
                @click="handleClick(index + 1)"
            >
                <!-- Full Star -->
                <svg
                    v-if="type === 'full'"
                    :class="sizeClasses"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="text-yellow-400"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z"
                        clip-rule="evenodd"
                    />
                </svg>
                
                <!-- Half Star -->
                <svg
                    v-else-if="type === 'half'"
                    :class="sizeClasses"
                    viewBox="0 0 20 20"
                    class="text-yellow-400"
                >
                    <defs>
                        <linearGradient :id="`half-${index}`">
                            <stop offset="50%" stop-color="currentColor" />
                            <stop offset="50%" stop-color="#e5e7eb" />
                        </linearGradient>
                    </defs>
                    <path
                        :fill="`url(#half-${index})`"
                        d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z"
                    />
                </svg>
                
                <!-- Empty Star -->
                <svg
                    v-else
                    :class="sizeClasses"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="text-gray-200"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
        </div>
        
        <span v-if="showValue" class="text-sm font-medium">
            {{ numericValue.toFixed(1) }}
        </span>
        
        <span v-if="reviewCount !== undefined" class="text-sm text-muted-foreground">
            ({{ reviewCount }})
        </span>
    </div>
</template>
