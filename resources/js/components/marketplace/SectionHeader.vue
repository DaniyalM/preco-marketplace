<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    title: string;
    viewAllHref?: string;
    viewAllLabel?: string;
    subtitle?: string;
}

withDefaults(defineProps<Props>(), {
    viewAllLabel: 'View all',
});
</script>

<template>
    <div class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight sm:text-2xl">{{ title }}</h2>
            <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">{{ subtitle }}</p>
        </div>
        <Link
            v-if="viewAllHref"
            :href="viewAllHref"
            class="inline-flex items-center text-sm font-semibold text-primary transition hover:underline"
        >
            {{ viewAllLabel }}
            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </Link>
    </div>
</template>
