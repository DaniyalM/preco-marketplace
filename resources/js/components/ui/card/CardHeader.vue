<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {}

const props = defineProps<Props>();

const classes = computed(() => cn('flex flex-col space-y-1.5 p-6', props.class));
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
