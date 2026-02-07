<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useCategoriesQuery } from '@/composables/useCategoriesApi';
import { computed } from 'vue';
import type { Category } from './CategoryCard.vue';

const { data: categoriesData } = useCategoriesQuery({ roots_only: true });
const categories = computed(() => {
    const raw = categoriesData.value;
    return Array.isArray(raw) ? (raw as Category[]) : [];
});

const dummyCategories: { name: string; slug: string; icon: string; color: string }[] = [
    { name: 'Electronics', slug: 'electronics', icon: '📱', color: 'bg-blue-500/10 text-blue-700 dark:text-blue-300' },
    { name: 'Fashion', slug: 'fashion', icon: '👗', color: 'bg-rose-500/10 text-rose-700 dark:text-rose-300' },
    { name: 'Home & Garden', slug: 'home-garden', icon: '🏠', color: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' },
    { name: 'Sports', slug: 'sports', icon: '⚽', color: 'bg-amber-500/10 text-amber-700 dark:text-amber-300' },
    { name: 'Beauty', slug: 'beauty', icon: '💄', color: 'bg-pink-500/10 text-pink-700 dark:text-pink-300' },
    { name: 'Toys', slug: 'toys', icon: '🧸', color: 'bg-violet-500/10 text-violet-700 dark:text-violet-300' },
];

const displayCategories = computed(() => {
    if (categories.value.length >= 4) {
        return categories.value.slice(0, 8).map((c) => ({
            name: c.name,
            slug: c.slug,
            href: `/categories/${c.slug}`,
            color: 'bg-primary/10 text-primary',
            icon: null,
        }));
    }
    return dummyCategories.map((c) => ({ ...c, href: `/categories/${c.slug}`, icon: c.icon }));
});
</script>

<template>
    <section class="py-6" aria-label="Shop by category">
        <div class="container mx-auto px-4">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-muted-foreground">Shop by category</h2>
            <div class="flex gap-3 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:thin]">
                <Link
                    v-for="cat in displayCategories"
                    :key="cat.slug"
                    :href="cat.href"
                    class="flex shrink-0 items-center gap-2.5 rounded-xl border border-border/60 bg-card px-4 py-3 text-sm font-medium shadow-sm transition-all hover:border-primary/30 hover:shadow-md"
                    :class="cat.color || 'bg-muted/50 text-foreground'"
                >
                    <span v-if="cat.icon" class="text-lg">{{ cat.icon }}</span>
                    <span>{{ cat.name }}</span>
                </Link>
                <Link
                    href="/categories"
                    class="flex shrink-0 items-center gap-2 rounded-xl border border-dashed border-muted-foreground/30 bg-transparent px-4 py-3 text-sm font-medium text-muted-foreground transition hover:border-primary/50 hover:text-primary"
                >
                    View all
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
        </div>
    </section>
</template>
