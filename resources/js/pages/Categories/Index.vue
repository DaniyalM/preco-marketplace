<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import { CategoryCard } from '@/components/marketplace';
import { EmptyState } from '@/components/common';
import { useCategoriesQuery } from '@/composables/useCategoriesApi';
import { computed } from 'vue';

const { data: categoriesData, isLoading: loading } = useCategoriesQuery({
    roots_only: true,
});

const categories = computed(() => (Array.isArray(categoriesData.value) ? categoriesData.value : []));
</script>

<template>
    <AppLayout title="Categories">
        <Head title="Shop by Category" />

        <div class="container mx-auto px-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold">Shop by Category</h1>
                <p class="mt-2 text-muted-foreground">
                    Browse our wide selection of product categories
                </p>
            </div>

            <!-- Loading State -->
            <div
                v-if="loading"
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div v-for="i in 8" :key="i" class="animate-pulse">
                    <div class="aspect-[4/3] rounded-lg bg-muted" />
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-else-if="categories.length === 0"
                icon="box"
                title="No categories yet"
                description="Categories will appear here once they're added."
            />

            <!-- Categories Grid -->
            <div
                v-else
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <CategoryCard
                    v-for="category in categories"
                    :key="category.id"
                    :category="category"
                />
            </div>
        </div>
    </AppLayout>
</template>
