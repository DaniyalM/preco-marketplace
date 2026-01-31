<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
import { computed, type HTMLAttributes } from 'vue';

export interface Category {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    icon?: string | null;
    product_count?: number;
}

interface Props extends /* @vue-ignore */ HTMLAttributes {
    category: Category;
    variant?: 'default' | 'compact' | 'icon';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});

const categoryUrl = computed(() => `/categories/${props.category.slug}`);
</script>

<template>
    <Link
        :href="categoryUrl"
        :class="cn(
            'group block overflow-hidden rounded-lg transition-all hover:shadow-lg',
            variant === 'icon' && 'text-center',
            $props.class
        )"
    >
        <!-- Default Variant -->
        <div v-if="variant === 'default'" class="relative aspect-[4/3] overflow-hidden">
            <img
                v-if="category.image"
                :src="category.image"
                :alt="category.name"
                class="h-full w-full object-cover transition-transform group-hover:scale-105"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/20 to-primary/5"
            >
                <span class="text-4xl font-bold text-primary/40">
                    {{ category.name[0] }}
                </span>
            </div>
            
            <!-- Overlay -->
            <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/60 to-transparent p-4">
                <div>
                    <h3 class="font-semibold text-white">{{ category.name }}</h3>
                    <p v-if="category.product_count !== undefined" class="text-sm text-white/80">
                        {{ category.product_count }} products
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Compact Variant -->
        <div v-else-if="variant === 'compact'" class="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-muted">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted">
                <img
                    v-if="category.image"
                    :src="category.image"
                    :alt="category.name"
                    class="h-full w-full object-cover"
                />
                <span v-else class="text-lg font-semibold text-muted-foreground">
                    {{ category.name[0] }}
                </span>
            </div>
            <div class="flex-1">
                <h3 class="font-medium">{{ category.name }}</h3>
                <p v-if="category.product_count !== undefined" class="text-sm text-muted-foreground">
                    {{ category.product_count }} products
                </p>
            </div>
            <svg class="h-5 w-5 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </div>
        
        <!-- Icon Variant -->
        <div v-else-if="variant === 'icon'" class="flex flex-col items-center p-4">
            <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 transition-colors group-hover:bg-primary/20">
                <img
                    v-if="category.icon"
                    :src="category.icon"
                    :alt="category.name"
                    class="h-8 w-8"
                />
                <span v-else class="text-2xl font-bold text-primary">
                    {{ category.name[0] }}
                </span>
            </div>
            <h3 class="text-sm font-medium">{{ category.name }}</h3>
        </div>
    </Link>
</template>
