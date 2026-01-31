<script setup lang="ts">
import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';
import { computed, type HTMLAttributes } from 'vue';

const avatarVariants = cva(
    'relative flex shrink-0 overflow-hidden rounded-full',
    {
        variants: {
            size: {
                sm: 'h-8 w-8',
                default: 'h-10 w-10',
                lg: 'h-12 w-12',
                xl: 'h-16 w-16',
            },
        },
        defaultVariants: {
            size: 'default',
        },
    }
);

export type AvatarVariants = VariantProps<typeof avatarVariants>;

interface Props extends /* @vue-ignore */ HTMLAttributes {
    size?: AvatarVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    size: 'default',
});

const classes = computed(() => cn(avatarVariants({ size: props.size }), props.class));
</script>

<template>
    <span :class="classes">
        <slot />
    </span>
</template>
