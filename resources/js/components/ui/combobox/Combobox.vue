<script setup lang="ts">
import { cn } from '@/lib/utils';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from 'radix-vue';
import { computed, ref, watch } from 'vue';

export interface ComboboxOption {
    value: string | number;
    label: string;
    disabled?: boolean;
    group?: string;
    icon?: string;
    description?: string;
}

interface Props {
    modelValue?: string | number | (string | number)[];
    options: ComboboxOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    multiple?: boolean;
    searchable?: boolean;
    clearable?: boolean;
    error?: boolean;
    loading?: boolean;
    class?: string;
    displayValue?: (option: ComboboxOption | ComboboxOption[]) => string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select an option...',
    searchPlaceholder: 'Search...',
    emptyText: 'No results found.',
    disabled: false,
    multiple: false,
    searchable: true,
    clearable: false,
    error: false,
    loading: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number | (string | number)[] | undefined];
    search: [query: string];
    clear: [];
}>();

const open = ref(false);
const searchQuery = ref('');

// Compute selected option(s) for display
const selectedOptions = computed(() => {
    if (!props.modelValue) return [];
    if (props.multiple && Array.isArray(props.modelValue)) {
        return props.options.filter((opt) => props.modelValue.includes(opt.value));
    }
    return props.options.filter((opt) => opt.value === props.modelValue);
});

// Filter options based on search query
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(
        (opt) =>
            opt.label.toLowerCase().includes(query) ||
            opt.description?.toLowerCase().includes(query)
    );
});

// Group options if they have group property
const groupedOptions = computed(() => {
    const groups: Record<string, ComboboxOption[]> = {};
    const ungrouped: ComboboxOption[] = [];

    filteredOptions.value.forEach((opt) => {
        if (opt.group) {
            if (!groups[opt.group]) {
                groups[opt.group] = [];
            }
            groups[opt.group].push(opt);
        } else {
            ungrouped.push(opt);
        }
    });

    return { groups, ungrouped };
});

const hasGroups = computed(() => Object.keys(groupedOptions.value.groups).length > 0);

// Display value for the trigger
const displayText = computed(() => {
    if (selectedOptions.value.length === 0) {
        return props.placeholder;
    }

    if (props.displayValue) {
        return props.displayValue(
            props.multiple ? selectedOptions.value : selectedOptions.value[0]
        );
    }

    if (props.multiple) {
        if (selectedOptions.value.length === 1) {
            return selectedOptions.value[0].label;
        }
        return `${selectedOptions.value.length} selected`;
    }

    return selectedOptions.value[0]?.label || props.placeholder;
});

const isSelected = (value: string | number) => {
    if (props.multiple && Array.isArray(props.modelValue)) {
        return props.modelValue.includes(value);
    }
    return props.modelValue === value;
};

const handleSelect = (value: string | number) => {
    if (props.multiple) {
        const currentValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
        const index = currentValue.indexOf(value);
        if (index > -1) {
            currentValue.splice(index, 1);
        } else {
            currentValue.push(value);
        }
        emit('update:modelValue', currentValue);
    } else {
        emit('update:modelValue', value);
        open.value = false;
    }
};

const handleClear = (e: Event) => {
    e.stopPropagation();
    emit('update:modelValue', props.multiple ? [] : undefined);
    emit('clear');
};

// Emit search events for external filtering
watch(searchQuery, (query) => {
    emit('search', query);
});

// Reset search when closed
watch(open, (isOpen) => {
    if (!isOpen) {
        searchQuery.value = '';
    }
});
</script>

<template>
    <ComboboxRoot v-model:open="open" :disabled="disabled" class="relative w-full">
        <ComboboxAnchor
            :class="
                cn(
                    'flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background focus-within:ring-1 focus-within:ring-ring',
                    error && 'border-destructive focus-within:ring-destructive',
                    disabled && 'cursor-not-allowed opacity-50',
                    props.class
                )
            "
        >
            <ComboboxInput
                v-if="searchable"
                v-model="searchQuery"
                :placeholder="
                    selectedOptions.length > 0 && !open ? displayText : searchPlaceholder
                "
                :disabled="disabled"
                :class="
                    cn(
                        'flex-1 bg-transparent outline-none placeholder:text-muted-foreground',
                        !open && selectedOptions.length > 0 && 'placeholder:text-foreground'
                    )
                "
                @focus="open = true"
            />
            <span v-else class="flex-1 truncate text-left">
                {{ displayText }}
            </span>

            <div class="flex items-center gap-1">
                <!-- Clear button -->
                <button
                    v-if="clearable && selectedOptions.length > 0"
                    type="button"
                    class="rounded-sm p-0.5 hover:bg-accent"
                    @click="handleClear"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>

                <!-- Loading spinner -->
                <svg
                    v-if="loading"
                    class="h-4 w-4 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>

                <!-- Chevron trigger -->
                <ComboboxTrigger v-else class="p-0.5">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :class="cn('transition-transform', open && 'rotate-180')"
                    >
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </ComboboxTrigger>
            </div>
        </ComboboxAnchor>

        <ComboboxContent
            :class="
                cn(
                    'absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md',
                    'data-[state=open]:animate-in data-[state=closed]:animate-out',
                    'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
                    'data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95'
                )
            "
            position="popper"
            :side-offset="4"
        >
            <ComboboxViewport class="p-1">
                <ComboboxEmpty class="py-6 text-center text-sm text-muted-foreground">
                    {{ emptyText }}
                </ComboboxEmpty>

                <!-- Ungrouped options -->
                <template v-if="!hasGroups">
                    <ComboboxItem
                        v-for="option in filteredOptions"
                        :key="option.value"
                        :value="String(option.value)"
                        :disabled="option.disabled"
                        :class="
                            cn(
                                'relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none',
                                'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                                'data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground'
                            )
                        "
                        @select.prevent="handleSelect(option.value)"
                    >
                        <span class="flex-1">
                            <span class="block">{{ option.label }}</span>
                            <span
                                v-if="option.description"
                                class="block text-xs text-muted-foreground"
                            >
                                {{ option.description }}
                            </span>
                        </span>
                        <ComboboxItemIndicator class="ml-2">
                            <svg
                                v-if="isSelected(option.value)"
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </template>

                <!-- Grouped options -->
                <template v-else>
                    <!-- Ungrouped first -->
                    <ComboboxItem
                        v-for="option in groupedOptions.ungrouped"
                        :key="option.value"
                        :value="String(option.value)"
                        :disabled="option.disabled"
                        :class="
                            cn(
                                'relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none',
                                'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                                'data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground'
                            )
                        "
                        @select.prevent="handleSelect(option.value)"
                    >
                        <span class="flex-1">{{ option.label }}</span>
                        <ComboboxItemIndicator class="ml-2">
                            <svg
                                v-if="isSelected(option.value)"
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </ComboboxItemIndicator>
                    </ComboboxItem>

                    <!-- Groups -->
                    <ComboboxGroup
                        v-for="(options, groupName) in groupedOptions.groups"
                        :key="groupName"
                        class="pt-2"
                    >
                        <div
                            class="px-2 py-1.5 text-xs font-medium text-muted-foreground"
                        >
                            {{ groupName }}
                        </div>
                        <ComboboxItem
                            v-for="option in options"
                            :key="option.value"
                            :value="String(option.value)"
                            :disabled="option.disabled"
                            :class="
                                cn(
                                    'relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none',
                                    'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                                    'data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground'
                                )
                            "
                            @select.prevent="handleSelect(option.value)"
                        >
                            <span class="flex-1">{{ option.label }}</span>
                            <ComboboxItemIndicator class="ml-2">
                                <svg
                                    v-if="isSelected(option.value)"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>
                </template>
            </ComboboxViewport>
        </ComboboxContent>
    </ComboboxRoot>
</template>
