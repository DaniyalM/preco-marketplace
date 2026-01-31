<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type LabelHTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ LabelHTMLAttributes {
    error?: boolean;
    required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    error: false,
    required: false,
});

const classes = computed(() =>
    cn(
        'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
        props.error && 'text-destructive',
        props.class
    )
);
</script>

<template>
    <label :class="classes">
        <slot />
        <span v-if="required" class="ml-1 text-destructive">*</span>
    </label>
</template>
