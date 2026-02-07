<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const items = [
    { text: 'Free shipping on orders over $49', href: '/products', icon: 'truck' },
    { text: 'Easy returns within 30 days', href: '/help', icon: 'refresh' },
    { text: 'Secure payment', href: '/checkout', icon: 'shield' },
];
</script>

<template>
    <div class="border-b bg-primary/8 py-2.5">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap items-center justify-center gap-6 text-sm font-medium text-foreground/90">
                <Link
                    v-for="(item, i) in items"
                    :key="i"
                    :href="item.href"
                    class="flex items-center gap-2 transition hover:text-primary"
                >
                    <span v-if="item.icon === 'truck'" class="flex h-5 w-5 items-center justify-center rounded bg-primary/15 text-primary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </span>
                    <span v-else-if="item.icon === 'refresh'" class="flex h-5 w-5 items-center justify-center rounded bg-primary/15 text-primary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </span>
                    <span v-else class="flex h-5 w-5 items-center justify-center rounded bg-primary/15 text-primary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </span>
                    {{ item.text }}
                </Link>
            </div>
        </div>
    </div>
</template>
