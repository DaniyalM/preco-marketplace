<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {
    as?: string;
}

const props = withDefaults(defineProps<Props>(), {
    as: 'h3',
});

const classes = computed(() =>
    cn('font-semibold leading-none tracking-tight', props.class)
);
</script>

<template>
    <component :is="as" :class="classes">
        <slot />
    </component>
</template>
