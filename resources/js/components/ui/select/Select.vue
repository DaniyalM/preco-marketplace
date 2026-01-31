<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type SelectHTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ SelectHTMLAttributes {
    modelValue?: string | number;
    error?: boolean;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    error: false,
    placeholder: 'Select an option',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const classes = computed(() =>
    cn(
        'flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
        props.error && 'border-destructive focus:ring-destructive',
        props.class
    )
);

const handleChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <select
        :class="classes"
        :value="modelValue"
        @change="handleChange"
    >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <slot />
    </select>
</template>
