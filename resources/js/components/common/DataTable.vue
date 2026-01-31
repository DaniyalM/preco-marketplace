<script setup lang="ts" generic="T">
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui';
import { computed, type HTMLAttributes } from 'vue';

interface Column<T> {
    key: string;
    label: string;
    sortable?: boolean;
    class?: string;
}

interface Props extends /* @vue-ignore */ HTMLAttributes {
    columns: Column<T>[];
    data: T[];
    loading?: boolean;
    sortKey?: string;
    sortOrder?: 'asc' | 'desc';
    emptyMessage?: string;
    rowKey?: string;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    emptyMessage: 'No data found',
    rowKey: 'id',
});

const emit = defineEmits<{
    sort: [key: string];
    rowClick: [row: T];
}>();

const getRowKey = (row: T, index: number) => {
    if (props.rowKey && typeof row === 'object' && row !== null) {
        return (row as Record<string, unknown>)[props.rowKey] ?? index;
    }
    return index;
};

const getCellValue = (row: T, key: string) => {
    if (typeof row === 'object' && row !== null) {
        const keys = key.split('.');
        let value: unknown = row;
        for (const k of keys) {
            value = (value as Record<string, unknown>)?.[k];
        }
        return value;
    }
    return '';
};
</script>

<template>
    <div :class="cn('overflow-hidden rounded-lg border', $props.class)">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            :class="cn(
                                'px-4 py-3 text-left text-sm font-medium text-muted-foreground',
                                column.sortable && 'cursor-pointer hover:text-foreground',
                                column.class
                            )"
                            @click="column.sortable && emit('sort', column.key)"
                        >
                            <div class="flex items-center gap-2">
                                {{ column.label }}
                                <svg
                                    v-if="column.sortable && sortKey === column.key"
                                    class="h-4 w-4"
                                    :class="sortOrder === 'desc' && 'rotate-180'"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading State -->
                    <tr v-if="loading">
                        <td :colspan="columns.length" class="py-12 text-center">
                            <div class="flex items-center justify-center">
                                <svg class="h-6 w-6 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Empty State -->
                    <tr v-else-if="data.length === 0">
                        <td :colspan="columns.length" class="py-12 text-center text-muted-foreground">
                            {{ emptyMessage }}
                        </td>
                    </tr>
                    
                    <!-- Data Rows -->
                    <tr
                        v-else
                        v-for="(row, index) in data"
                        :key="getRowKey(row, index)"
                        class="border-b last:border-0 hover:bg-muted/50 transition-colors cursor-pointer"
                        @click="emit('rowClick', row)"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            :class="cn('px-4 py-3 text-sm', column.class)"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :value="getCellValue(row, column.key)">
                                {{ getCellValue(row, column.key) }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
