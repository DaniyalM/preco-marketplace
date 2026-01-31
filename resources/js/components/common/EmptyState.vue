<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui';
import type { HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {
    title: string;
    description?: string;
    icon?: 'box' | 'cart' | 'search' | 'user' | 'store' | 'order' | 'heart';
    actionLabel?: string;
    actionHref?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    action: [];
}>();
</script>

<template>
    <div :class="cn('flex flex-col items-center justify-center py-12 text-center', $props.class)">
        <!-- Icons -->
        <div class="mb-4 rounded-full bg-muted p-4">
            <!-- Box/Package Icon -->
            <svg
                v-if="icon === 'box'"
                class="h-8 w-8 text-muted-foreground"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            
            <!-- Cart Icon -->
            <svg
                v-else-if="icon === 'cart'"
                class="h-8 w-8 text-muted-foreground"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            
            <!-- Search Icon -->
            <svg
                v-else-if="icon === 'search'"
                class="h-8 w-8 text-muted-foreground"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            
            <!-- User Icon -->
            <svg
                v-else-if="icon === 'user'"
                class="h-8 w-8 text-muted-foreground"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            
            <!-- Store Icon -->
            <svg
                v-else-if="icon === 'store'"
                class="h-8 w-8 text-muted-foreground"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18M3 7h18M6 11v8m12-8v8M6 11h12M3 21h18" />
            </svg>
            
            <!-- Order Icon -->
            <svg
                v-else-if="icon === 'order'"
                class="h-8 w-8 text-muted-foreground"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            
            <!-- Heart Icon -->
            <svg
                v-else-if="icon === 'heart'"
                class="h-8 w-8 text-muted-foreground"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            
            <!-- Default Icon -->
            <svg
                v-else
                class="h-8 w-8 text-muted-foreground"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        </div>
        
        <h3 class="mb-2 text-lg font-semibold">{{ title }}</h3>
        <p v-if="description" class="mb-6 max-w-sm text-sm text-muted-foreground">
            {{ description }}
        </p>
        
        <Button
            v-if="actionLabel"
            :as="actionHref ? 'a' : 'button'"
            :href="actionHref"
            @click="emit('action')"
        >
            {{ actionLabel }}
        </Button>
    </div>
</template>
