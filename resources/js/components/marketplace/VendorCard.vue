<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Card, CardContent, Badge, Avatar, AvatarImage, AvatarFallback } from '@/components/ui';
import { Rating } from '@/components/common';
import { Link } from '@inertiajs/vue3';
import { computed, type HTMLAttributes } from 'vue';

export interface Vendor {
    id: number;
    business_name: string;
    slug: string;
    logo?: string | null;
    banner?: string | null;
    description?: string | null;
    is_featured?: boolean;
    product_count?: number;
    average_rating?: number;
    review_count?: number;
}

interface Props extends /* @vue-ignore */ HTMLAttributes {
    vendor: Vendor;
    variant?: 'default' | 'compact';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});

const vendorUrl = computed(() => `/vendors/${props.vendor.slug}`);

const initials = computed(() => {
    const words = props.vendor.business_name.split(' ');
    return words.map(w => w[0]).join('').substring(0, 2).toUpperCase();
});
</script>

<template>
    <Card :class="cn('overflow-hidden transition-shadow hover:shadow-lg', $props.class)">
        <!-- Banner (default variant only) -->
        <Link
            v-if="variant === 'default'"
            :href="vendorUrl"
            class="relative block h-24 overflow-hidden bg-gradient-to-r from-primary/20 to-primary/10"
        >
            <img
                v-if="vendor.banner"
                :src="vendor.banner"
                :alt="vendor.business_name"
                class="h-full w-full object-cover"
            />
        </Link>
        
        <CardContent :class="variant === 'default' ? 'relative pt-10' : 'p-4'">
            <!-- Logo -->
            <div
                v-if="variant === 'default'"
                class="absolute -top-8 left-4"
            >
                <Avatar size="xl" class="border-4 border-background">
                    <AvatarImage v-if="vendor.logo" :src="vendor.logo" />
                    <AvatarFallback>{{ initials }}</AvatarFallback>
                </Avatar>
            </div>
            
            <div :class="cn('flex gap-3', variant === 'compact' && 'items-center')">
                <!-- Logo (compact variant) -->
                <Avatar v-if="variant === 'compact'">
                    <AvatarImage v-if="vendor.logo" :src="vendor.logo" />
                    <AvatarFallback>{{ initials }}</AvatarFallback>
                </Avatar>
                
                <div class="flex-1">
                    <!-- Name & Badge -->
                    <div class="mb-1 flex items-center gap-2">
                        <Link :href="vendorUrl">
                            <h3 class="font-semibold transition-colors hover:text-primary">
                                {{ vendor.business_name }}
                            </h3>
                        </Link>
                        <Badge v-if="vendor.is_featured" variant="secondary" class="text-xs">
                            Featured
                        </Badge>
                    </div>
                    
                    <!-- Description (default variant only) -->
                    <p
                        v-if="variant === 'default' && vendor.description"
                        class="mb-3 line-clamp-2 text-sm text-muted-foreground"
                    >
                        {{ vendor.description }}
                    </p>
                    
                    <!-- Stats -->
                    <div class="flex items-center gap-4 text-sm text-muted-foreground">
                        <Rating
                            v-if="vendor.average_rating !== undefined"
                            :value="vendor.average_rating"
                            :review-count="vendor.review_count"
                            size="sm"
                        />
                        
                        <span v-if="vendor.product_count !== undefined" class="flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            {{ vendor.product_count }} products
                        </span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
