<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Button, Avatar, AvatarImage, AvatarFallback, Badge, Separator } from '@/components/ui';
import { StatusBadge } from '@/components/common';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    title?: string;
}

defineProps<Props>();

const page = usePage();

const user = computed(() => page.props.auth?.user);
const vendor = computed(() => page.props.auth?.vendor);

const sidebarOpen = ref(true);
const mobileMenuOpen = ref(false);

const initials = computed(() => {
    if (!vendor.value?.business_name) return '?';
    const words = vendor.value.business_name.split(' ');
    return words.map((w: string) => w[0]).join('').substring(0, 2).toUpperCase();
});

const navigation = [
    { 
        name: 'Dashboard', 
        href: '/vendor', 
        icon: 'dashboard',
    },
    { 
        name: 'Products', 
        href: '/vendor/products', 
        icon: 'products',
    },
    { 
        name: 'Orders', 
        href: '/vendor/orders', 
        icon: 'orders',
    },
    { 
        name: 'Analytics', 
        href: '/vendor/analytics', 
        icon: 'analytics',
    },
    { 
        name: 'Payouts', 
        href: '/vendor/payouts', 
        icon: 'payouts',
    },
    { 
        name: 'Settings', 
        href: '/vendor/settings', 
        icon: 'settings',
    },
];

const isActive = (href: string) => {
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    return currentPath === href || currentPath.startsWith(href + '/');
};
</script>

<template>
    <div class="flex min-h-screen bg-muted/30">
        <!-- Sidebar -->
        <aside
            :class="cn(
                'fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r bg-background transition-transform lg:translate-x-0',
                mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
            )"
        >
            <!-- Logo -->
            <div class="flex h-16 items-center gap-2 border-b px-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                    <span class="text-sm font-bold text-primary-foreground">P</span>
                </div>
                <div>
                    <span class="font-bold">Vendor Portal</span>
                    <Badge variant="secondary" class="ml-2 text-xs">Beta</Badge>
                </div>
            </div>
            
            <!-- Vendor Info -->
            <div class="border-b p-4">
                <div class="flex items-center gap-3">
                    <Avatar>
                        <AvatarImage v-if="vendor?.logo" :src="vendor.logo" />
                        <AvatarFallback>{{ initials }}</AvatarFallback>
                    </Avatar>
                    <div class="flex-1 overflow-hidden">
                        <p class="truncate font-medium">{{ vendor?.business_name || 'My Store' }}</p>
                        <StatusBadge v-if="vendor?.status" :status="vendor.status" type="vendor" />
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <ul class="space-y-1">
                    <li v-for="item in navigation" :key="item.name">
                        <Link
                            :href="item.href"
                            :class="cn(
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                isActive(item.href)
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            )"
                        >
                            <!-- Icons -->
                            <svg v-if="item.icon === 'dashboard'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'products'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <svg v-else-if="item.icon === 'orders'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <svg v-else-if="item.icon === 'analytics'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'payouts'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="item.icon === 'settings'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            
                            {{ item.name }}
                        </Link>
                    </li>
                </ul>
            </nav>
            
            <!-- Footer -->
            <div class="border-t p-4">
                <Link
                    href="/"
                    class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Marketplace
                </Link>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex flex-1 flex-col lg:pl-64">
            <!-- Top Bar -->
            <header class="sticky top-0 z-40 flex h-16 items-center gap-4 border-b bg-background px-4 lg:px-6">
                <!-- Mobile Menu Toggle -->
                <Button
                    variant="ghost"
                    size="icon"
                    class="lg:hidden"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </Button>
                
                <!-- Page Title -->
                <h1 v-if="title" class="text-lg font-semibold">{{ title }}</h1>
                
                <div class="flex-1" />
                
                <!-- User Menu -->
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </Button>
                    
                    <Separator orientation="vertical" class="h-6" />
                    
                    <div class="flex items-center gap-2">
                        <Avatar size="sm">
                            <AvatarFallback>{{ initials }}</AvatarFallback>
                        </Avatar>
                        <span class="hidden text-sm font-medium md:inline">{{ user?.name }}</span>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1 p-4 lg:p-6">
                <slot />
            </main>
        </div>
        
        <!-- Mobile Overlay -->
        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            @click="mobileMenuOpen = false"
        />
    </div>
</template>
