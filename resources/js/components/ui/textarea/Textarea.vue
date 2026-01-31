<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type TextareaHTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ TextareaHTMLAttributes {
    modelValue?: string;
    error?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    error: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const classes = computed(() =>
    cn(
        'flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
        props.error && 'border-destructive focus-visible:ring-destructive',
        props.class
    )
);

const handleInput = (event: Event) => {
    const target = event.target as HTMLTextAreaElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <textarea
        :class="classes"
        :value="modelValue"
        @input="handleInput"
    />
</template>
