<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type InputHTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ InputHTMLAttributes {
    modelValue?: string | number;
    error?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    error: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const classes = computed(() =>
    cn(
        'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
        props.error && 'border-destructive focus-visible:ring-destructive',
        props.class
    )
);

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <input
        :class="classes"
        :value="modelValue"
        @input="handleInput"
    />
</template>
