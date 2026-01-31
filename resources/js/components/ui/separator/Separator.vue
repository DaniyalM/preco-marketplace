<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {
    orientation?: 'horizontal' | 'vertical';
    decorative?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    orientation: 'horizontal',
    decorative: true,
});

const classes = computed(() =>
    cn(
        'shrink-0 bg-border',
        props.orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]',
        props.class
    )
);
</script>

<template>
    <div
        :class="classes"
        :role="decorative ? 'none' : 'separator'"
        :aria-orientation="decorative ? undefined : orientation"
    />
</template>
