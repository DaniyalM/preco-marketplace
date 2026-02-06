<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Input, Label, Textarea, Combobox } from '@/components/ui';
import { computed, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {
    modelValue?: string | number;
    label?: string;
    type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'date' | 'textarea' | 'select';
    placeholder?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    disabled?: boolean;
    rows?: number;
    options?: Array<{ value: string | number; label: string }>;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    type: 'text',
    placeholder: '',
    required: false,
    disabled: false,
    rows: 3,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const hasError = computed(() => !!props.error);

const inputId = computed(() => `field-${Math.random().toString(36).substr(2, 9)}`);
</script>

<template>
    <div :class="cn('space-y-2', $props.class)">
        <Label
            v-if="label"
            :for="inputId"
            :error="hasError"
            :required="required"
        >
            {{ label }}
        </Label>
        
        <Textarea
            v-if="type === 'textarea'"
            :id="inputId"
            :model-value="String(modelValue)"
            :placeholder="placeholder"
            :disabled="disabled"
            :error="hasError"
            :rows="rows"
            @update:model-value="(v) => emit('update:modelValue', v)"
        />
        
        <Combobox
            v-else-if="type === 'select'"
            :model-value="modelValue"
            :options="options ?? []"
            :placeholder="placeholder || 'Select an option...'"
            :disabled="disabled"
            :error="hasError"
            :searchable="true"
            @update:model-value="(v) => emit('update:modelValue', v)"
        />
        
        <Input
            v-else
            :id="inputId"
            :type="type"
            :model-value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :error="hasError"
            @update:model-value="(v) => emit('update:modelValue', v)"
        />
        
        <p v-if="error" class="text-sm text-destructive">
            {{ error }}
        </p>
        <p v-else-if="hint" class="text-sm text-muted-foreground">
            {{ hint }}
        </p>
    </div>
</template>
