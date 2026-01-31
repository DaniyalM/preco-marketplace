<script setup lang="ts">
import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';
import { computed, type HTMLAttributes } from 'vue';

const alertVariants = cva(
    'relative w-full rounded-lg border px-4 py-3 text-sm [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg]:text-foreground [&>svg~*]:pl-7',
    {
        variants: {
            variant: {
                default: 'bg-background text-foreground',
                destructive: 'border-destructive/50 text-destructive dark:border-destructive [&>svg]:text-destructive',
                success: 'border-green-500/50 text-green-700 dark:border-green-500 [&>svg]:text-green-500 bg-green-50 dark:bg-green-950',
                warning: 'border-yellow-500/50 text-yellow-700 dark:border-yellow-500 [&>svg]:text-yellow-500 bg-yellow-50 dark:bg-yellow-950',
                info: 'border-blue-500/50 text-blue-700 dark:border-blue-500 [&>svg]:text-blue-500 bg-blue-50 dark:bg-blue-950',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export type AlertVariants = VariantProps<typeof alertVariants>;

interface Props extends /* @vue-ignore */ HTMLAttributes {
    variant?: AlertVariants['variant'];
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});

const classes = computed(() => cn(alertVariants({ variant: props.variant }), props.class));
</script>

<template>
    <div :class="classes" role="alert">
        <slot />
    </div>
</template>
