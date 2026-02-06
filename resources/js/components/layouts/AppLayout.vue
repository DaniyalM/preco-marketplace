<script setup lang="ts">
import { Avatar, AvatarFallback, Badge, Button, Toaster } from '@/components/ui';
import { useCartQuery } from '@/composables/useCartApi';
import { useLocale } from '@/composables/useLocale';
import { useAuthStore } from '@/stores/auth';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const authStore = useAuthStore();
const { locale, setLocale, t, supportedLocales } = useLocale();

interface Props {
    title?: string;
}

defineProps<Props>();

onMounted(() => {
    // if (!authStore.isAuthenticated && !authStore.meFetched) {
    authStore.fetchMe();
    // }
});

const user = computed(() => authStore.user);
const isAuthenticated = computed(() => authStore.isAuthenticated);

const { data: cartData } = useCartQuery({
    enabled: isAuthenticated,
});
const cartItemCount = computed(() => cartData.value?.item_count ?? 0);

const mobileMenuOpen = ref(false);
const userMenuOpen = ref(false);

const initials = computed(() => {
    if (!user.value?.name) return '?';
    const words = user.value.name.split(' ');
    return words
        .map((w: string) => w[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
});

const navigation = computed(() => [
    { key: 'nav.home', href: isAuthenticated.value ? '/home' : '/' },
    { key: 'nav.products', href: '/products' },
    { key: 'nav.categories', href: '/categories' },
    { key: 'nav.vendors', href: '/vendors' },
]);

const userNavigation = computed(() => {
    const items = [
        { key: 'nav.myOrders', href: '/orders' },
        { key: 'nav.wishlist', href: '/wishlist' },
        { key: 'nav.profile', href: '/profile' },
    ];

    if (user.value?.is_vendor) {
        items.unshift({ key: 'nav.vendorDashboard', href: '/vendor' });
    }
    if (user.value?.is_admin) {
        items.unshift({ key: 'nav.adminDashboard', href: '/admin' });
    }

    return items;
});

const localeMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                            <span class="text-sm font-bold text-primary-foreground">P</span>
                        </div>
                        <span class="text-xl font-bold">P-Commerce</span>
                    </Link>

                    <!-- Desktop Navigation -->
                    <nav class="hidden items-center gap-6 md:flex">
                        <Link
                            v-for="item in navigation"
                            :key="item.key"
                            :href="item.href"
                            class="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            {{ t(item.key) }}
                        </Link>
                    </nav>

                    <!-- Right Section -->
                    <div class="flex items-center gap-4">
                        <!-- Locale switcher -->
                        <div class="relative">
                            <button
                                type="button"
                                class="flex items-center gap-1 rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                                @click="localeMenuOpen = !localeMenuOpen"
                            >
                                <span>{{ locale === 'ar' ? 'العربية' : 'EN' }}</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div
                                v-if="localeMenuOpen"
                                class="absolute end-0 top-full z-50 mt-1 min-w-[8rem] rounded-lg border bg-background py-1 shadow-lg"
                                @click="localeMenuOpen = false"
                            >
                                <button
                                    v-for="loc in supportedLocales"
                                    :key="loc"
                                    type="button"
                                    class="block w-full px-3 py-2 text-start text-sm hover:bg-muted"
                                    :class="{ 'bg-muted font-medium': locale === loc }"
                                    @click="setLocale(loc)"
                                >
                                    {{ t('locale.' + loc) }}
                                </button>
                            </div>
                        </div>
                        <!-- Search -->
                        <Button variant="ghost" size="icon" class="hidden md:flex">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </Button>

                        <!-- Cart -->
                        <Link href="/cart">
                            <Button variant="ghost" size="icon" class="relative">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                                <Badge class="absolute -top-1 -right-1 h-5 w-5 rounded-full p-0 text-xs">
                                    {{ cartItemCount }}
                                </Badge>
                            </Button>
                        </Link>

                        <!-- User Menu -->
                        <div v-if="isAuthenticated" class="relative">
                            <button class="flex items-center gap-2" @click="userMenuOpen = !userMenuOpen">
                                <Avatar size="sm">
                                    <AvatarFallback>{{ initials }}</AvatarFallback>
                                </Avatar>
                            </button>

                            <!-- Dropdown -->
                            <div
                                v-if="userMenuOpen"
                                class="absolute top-full right-0 mt-2 w-56 rounded-lg border bg-background p-2 shadow-lg"
                                @click="userMenuOpen = false"
                            >
                                <div class="border-b px-3 py-2">
                                    <p class="font-medium">{{ user?.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ user?.email }}</p>
                                </div>
                                <div class="py-2">
                                    <Link
                                        v-for="item in userNavigation"
                                        :key="item.name"
                                        :href="item.href"
                                        class="block rounded-md px-3 py-2 text-sm transition-colors hover:bg-muted"
                                    >
                                        {{ item.name }}
                                    </Link>
                                </div>
                                <div class="border-t pt-2">
                                    <button
                                        type="button"
                                        class="w-full rounded-md px-3 py-2 text-left text-sm text-destructive transition-colors hover:bg-destructive/10"
                                        @click="authStore.logout()"
                                    >
                                        Sign out
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Login Button -->
                        <Button v-else as="a" href="/login">{{ t('nav.signIn') }}</Button>

                        <!-- Mobile Menu Toggle -->
                        <Button variant="ghost" size="icon" class="md:hidden" @click="mobileMenuOpen = !mobileMenuOpen">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-if="mobileMenuOpen" class="border-t md:hidden">
                <div class="container mx-auto px-4 py-4">
                    <nav class="flex flex-col gap-2">
                        <Link
                            v-for="item in navigation"
                            :key="item.key"
                            :href="item.href"
                            class="rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-muted"
                            @click="mobileMenuOpen = false"
                        >
                            {{ t(item.key) }}
                        </Link>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Page Title -->
        <div v-if="title" class="border-b bg-muted/30">
            <div class="container mx-auto px-4 py-8">
                <h1 class="text-3xl font-bold">{{ title }}</h1>
            </div>
        </div>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <Toaster />

        <!-- Footer -->
        <footer class="border-t bg-muted/30">
            <div class="container mx-auto px-4 py-12">
                <div class="grid gap-8 md:grid-cols-4">
                    <div>
                        <div class="mb-4 flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                                <span class="text-sm font-bold text-primary-foreground">P</span>
                            </div>
                            <span class="text-xl font-bold">P-Commerce</span>
                        </div>
                        <p class="text-sm text-muted-foreground">Your trusted multi-vendor marketplace for quality products.</p>
                    </div>

                    <div>
                        <h4 class="mb-4 font-semibold">{{ t('footer.shop') }}</h4>
                        <nav class="flex flex-col gap-2 text-sm text-muted-foreground">
                            <Link href="/products" class="hover:text-foreground">{{ t('footer.allProducts') }}</Link>
                            <Link href="/categories" class="hover:text-foreground">{{ t('nav.categories') }}</Link>
                            <Link href="/vendors" class="hover:text-foreground">{{ t('nav.vendors') }}</Link>
                            <Link href="/deals" class="hover:text-foreground">{{ t('footer.deals') }}</Link>
                        </nav>
                    </div>

                    <div>
                        <h4 class="mb-4 font-semibold">{{ t('footer.sell') }}</h4>
                        <nav class="flex flex-col gap-2 text-sm text-muted-foreground">
                            <Link href="/vendor/register" class="hover:text-foreground">{{ t('footer.becomeVendor') }}</Link>
                            <Link href="/vendor/login" class="hover:text-foreground">{{ t('footer.vendorLogin') }}</Link>
                            <Link href="/seller-guide" class="hover:text-foreground">{{ t('footer.sellerGuide') }}</Link>
                        </nav>
                    </div>

                    <div>
                        <h4 class="mb-4 font-semibold">{{ t('footer.support') }}</h4>
                        <nav class="flex flex-col gap-2 text-sm text-muted-foreground">
                            <Link href="/help" class="hover:text-foreground">{{ t('footer.helpCenter') }}</Link>
                            <Link href="/contact" class="hover:text-foreground">{{ t('footer.contactUs') }}</Link>
                            <Link href="/privacy" class="hover:text-foreground">{{ t('footer.privacy') }}</Link>
                            <Link href="/terms" class="hover:text-foreground">{{ t('footer.terms') }}</Link>
                        </nav>
                    </div>
                </div>

                <div class="mt-8 border-t pt-8 text-center text-sm text-muted-foreground">
                    <p>{{ t('footer.copyright', { year: new Date().getFullYear() }) }}</p>
                </div>
            </div>
        </footer>
    </div>
</template>
