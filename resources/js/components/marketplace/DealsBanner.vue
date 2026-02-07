<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link
        href="/products?sort=popularity&order=desc"
        class="group relative block overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-6 shadow-sm transition hover:border-amber-300 hover:shadow-lg dark:border-amber-700 dark:from-amber-950/30 dark:to-orange-950/30"
    >
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="inline-block rounded-full bg-amber-500/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-200">Limited time</span>
                <h3 class="mt-3 text-2xl font-bold text-foreground sm:text-3xl">Today’s top deals</h3>
                <p class="mt-1 text-muted-foreground">Best-selling products at great prices. Shop before they’re gone.</p>
            </div>
            <span class="mt-4 inline-flex items-center font-semibold text-primary sm:mt-0">
                Shop deals
                <svg class="ml-2 h-5 w-5 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </span>
        </div>
        <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-amber-400/20 blur-2xl" />
    </Link>
</template>
