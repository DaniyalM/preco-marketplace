<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {}

const props = defineProps<Props>();

const classes = computed(() => cn('text-sm text-muted-foreground', props.class));
</script>

<template>
    <p :class="classes">
        <slot />
    </p>
</template>
