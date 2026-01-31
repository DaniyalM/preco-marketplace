<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {}

const props = defineProps<Props>();

const classes = computed(() => cn('mb-1 font-medium leading-none tracking-tight', props.class));
</script>

<template>
    <h5 :class="classes">
        <slot />
    </h5>
</template>
