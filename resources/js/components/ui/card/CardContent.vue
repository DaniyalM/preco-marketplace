<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {}

const props = defineProps<Props>();

const classes = computed(() => cn('p-6 pt-0', props.class));
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
