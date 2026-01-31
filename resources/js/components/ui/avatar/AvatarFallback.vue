<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {}

const props = defineProps<Props>();

const classes = computed(() =>
    cn(
        'flex h-full w-full items-center justify-center rounded-full bg-muted',
        props.class
    )
);
</script>

<template>
    <span :class="classes">
        <slot />
    </span>
</template>
