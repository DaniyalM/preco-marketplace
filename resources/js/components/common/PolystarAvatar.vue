<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

/**
 * Star-shaped (polystar) avatar for feed-style UI.
 * Uses clip-path for an 6-point star shape.
 */
interface Props extends /* @vue-ignore */ HTMLAttributes {
    src?: string | null;
    name?: string;
    size?: 'sm' | 'default' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'default',
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-9 w-9';
        case 'lg':
            return 'h-12 w-12';
        default:
            return 'h-10 w-10';
    }
});

const initials = computed(() => {
    if (!props.name?.trim()) return '?';
    return props.name
        .trim()
        .split(/\s+/)
        .map((w) => w[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
});
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex shrink-0 items-center justify-center overflow-hidden bg-primary/15 text-primary font-semibold',
                sizeClasses,
                $props.class,
            )
        "
        style="clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);"
    >
        <img
            v-if="src"
            :src="src"
            :alt="name ?? ''"
            class="h-full w-full object-cover"
        />
        <span v-else class="text-[0.55em] leading-none">{{ initials }}</span>
    </span>
</template>
