<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Button, Avatar, AvatarFallback, Badge, Separator } from '@/components/ui';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    title?: string;
}

defineProps<Props>();

const page = usePage();

const user = computed(() => page.props.auth?.user);

const mobileMenuOpen = ref(false);

const initials = computed(() => {
    if (!user.value?.name) return 'A';
    const words = user.value.name.split(' ');
    return words.map((w: string) => w[0]).join('').substring(0, 2).toUpperCase();
});

const navigation = [
    { 
        name: 'Dashboard', 
        href: '/admin', 
        icon: 'dashboard',
    },
    { 
        name: 'Vendors', 
        href: '/admin/vendors', 
        icon: 'vendors',
        badge: null,
    },
    { 
        name: 'KYC Reviews', 
        href: '/admin/kyc', 
        icon: 'kyc',
    },
    { 
        name: 'Products', 
        href: '/admin/products', 
        icon: 'products',
    },
    { 
        name: 'Orders', 
        href: '/admin/orders', 
        icon: 'orders',
    },
    { 
        name: 'Categories', 
        href: '/admin/categories', 
        icon: 'categories',
    },
    { 
        name: 'Customers', 
        href: '/admin/customers', 
        icon: 'customers',
    },
    { 
        name: 'Reports', 
        href: '/admin/reports', 
        icon: 'reports',
    },
    { 
        name: 'Settings', 
        href: '/admin/settings', 
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
                'fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-border bg-card text-card-foreground transition-transform lg:translate-x-0',
                mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
            )"
        >
            <!-- Logo -->
            <div class="flex h-16 items-center gap-2 border-b border-border px-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                    <span class="text-sm font-bold text-primary-foreground">P</span>
                </div>
                <div>
                    <span class="font-bold">Admin Panel</span>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <ul class="space-y-1">
                    <li v-for="item in navigation" :key="item.name">
                        <Link
                            :href="item.href"
                            :class="cn(
                                'flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                isActive(item.href)
                                    ? 'bg-primary/10 text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            )"
                        >
                            <div class="flex items-center gap-3">
                                <!-- Icons -->
                                <svg v-if="item.icon === 'dashboard'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <svg v-else-if="item.icon === 'vendors'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <svg v-else-if="item.icon === 'kyc'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <svg v-else-if="item.icon === 'products'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <svg v-else-if="item.icon === 'orders'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <svg v-else-if="item.icon === 'categories'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <svg v-else-if="item.icon === 'customers'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <svg v-else-if="item.icon === 'reports'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <svg v-else-if="item.icon === 'settings'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                
                                {{ item.name }}
                            </div>
                            
                            <Badge v-if="item.badge" variant="destructive" class="text-xs">
                                {{ item.badge }}
                            </Badge>
                        </Link>
                    </li>
                </ul>
            </nav>
            
            <!-- Footer -->
            <div class="border-t border-border p-4">
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
                            <AvatarFallback class="bg-muted text-foreground">{{ initials }}</AvatarFallback>
                        </Avatar>
                        <div class="hidden md:block">
                            <p class="text-sm font-medium">{{ user?.name }}</p>
                            <p class="text-xs text-muted-foreground">Administrator</p>
                        </div>
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
