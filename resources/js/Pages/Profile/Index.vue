<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { AppLayout } from '@/components/layouts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Button,
    Avatar,
    AvatarFallback,
    Separator,
} from '@/components/ui';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const initials = computed(() => {
    if (!user.value?.name) return '?';
    const words = user.value.name.split(' ');
    return words.map((w: string) => w[0]).join('').substring(0, 2).toUpperCase();
});

const menuItems = [
    { name: 'My Orders', href: '/orders', icon: 'order', description: 'View and track your orders' },
    { name: 'Wishlist', href: '/wishlist', icon: 'heart', description: 'Products you saved for later' },
    { name: 'Addresses', href: '/profile/addresses', icon: 'address', description: 'Manage shipping addresses' },
];
</script>

<template>
    <AppLayout title="My Profile">
        <Head title="My Profile" />

        <div class="container mx-auto max-w-4xl px-4 py-8">
            <!-- Profile Header -->
            <Card class="mb-8">
                <CardContent class="p-6">
                    <div class="flex items-center gap-6">
                        <Avatar class="h-20 w-20">
                            <AvatarFallback class="text-2xl">{{ initials }}</AvatarFallback>
                        </Avatar>
                        <div>
                            <h1 class="text-2xl font-bold">{{ user?.name || 'User' }}</h1>
                            <p class="text-muted-foreground">{{ user?.email }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Menu -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="item in menuItems"
                    :key="item.name"
                    :href="item.href"
                >
                    <Card class="h-full transition-shadow hover:shadow-lg">
                        <CardContent class="flex items-center gap-4 p-6">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                <!-- Order Icon -->
                                <svg v-if="item.icon === 'order'" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                
                                <!-- Heart Icon -->
                                <svg v-else-if="item.icon === 'heart'" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                
                                <!-- Address Icon -->
                                <svg v-else-if="item.icon === 'address'" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold">{{ item.name }}</h3>
                                <p class="text-sm text-muted-foreground">{{ item.description }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <!-- Vendor Section -->
            <Card v-if="user?.is_vendor" class="mt-8">
                <CardHeader>
                    <CardTitle>Vendor Account</CardTitle>
                    <CardDescription>Manage your vendor profile and products</CardDescription>
                </CardHeader>
                <CardContent>
                    <Link href="/vendor">
                        <Button>Go to Vendor Dashboard</Button>
                    </Link>
                </CardContent>
            </Card>

            <!-- Become a Vendor -->
            <Card v-else class="mt-8">
                <CardHeader>
                    <CardTitle>Start Selling</CardTitle>
                    <CardDescription>Join our marketplace and reach thousands of customers</CardDescription>
                </CardHeader>
                <CardContent>
                    <Link href="/vendor/onboarding">
                        <Button>Become a Vendor</Button>
                    </Link>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
