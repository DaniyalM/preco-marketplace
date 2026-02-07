<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useLocale } from '@/composables/useLocale';
import { useTheme } from '@/composables/useTheme';
import { Button } from '@/components/ui';
import { ref } from 'vue';

const { t } = useLocale();
const { mode: themeMode, setLight, setDark, setSystem } = useTheme();
const themeOpen = ref(false);
</script>

<template>
    <Head :title="t('welcome.title')" />
    <div class="min-h-screen bg-background text-foreground">
        <!-- Simple header -->
        <header class="sticky top-0 z-10 border-b border-border bg-background/80 backdrop-blur-sm">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
                <span class="text-lg font-semibold">{{ t('welcome.title') }}</span>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <button
                            type="button"
                            class="rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-foreground"
                            aria-label="Theme"
                            @click="themeOpen = !themeOpen"
                        >
                            <span v-if="themeMode === 'light'">☀️</span>
                            <span v-else-if="themeMode === 'dark'">🌙</span>
                            <span v-else>💻</span>
                        </button>
                        <div
                            v-if="themeOpen"
                            class="absolute end-0 top-full z-50 mt-1 min-w-[7rem] rounded-lg border border-border bg-popover py-1 shadow-lg"
                            @click="themeOpen = false"
                        >
                            <button type="button" class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-muted" @click="setLight">Light</button>
                            <button type="button" class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-muted" @click="setDark">Dark</button>
                            <button type="button" class="w-full px-3 py-2 text-left text-sm text-foreground hover:bg-muted" @click="setSystem">System</button>
                        </div>
                    </div>
                    <Link href="/login">
                        <Button variant="ghost" size="sm">{{ t('welcome.signIn') }}</Button>
                    </Link>
                    <Link href="/products">
                        <Button size="sm">{{ t('welcome.shopNow') }}</Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <main class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 lg:py-32">
            <div class="text-center">
                <span class="inline-block rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-sm font-medium text-primary">
                    {{ t('welcome.tagline') }}
                </span>
                <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    {{ t('welcome.heroTitle') }}
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground">
                    {{ t('welcome.heroSubtitle') }}
                </p>
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <Link href="/products">
                        <Button size="lg" class="min-w-[160px] shadow-lg">
                            {{ t('welcome.shopNow') }}
                        </Button>
                    </Link>
                    <Link href="/vendor/onboarding">
                        <Button size="lg" variant="outline" class="min-w-[160px]">
                            {{ t('welcome.becomeVendor') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Trust strip -->
            <div class="mt-20 grid grid-cols-2 gap-6 rounded-2xl border border-border bg-card/50 p-6 sm:grid-cols-4">
                <div class="text-center">
                    <p class="text-2xl font-bold text-primary">1000+</p>
                    <p class="text-sm text-muted-foreground">Products</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-primary">50+</p>
                    <p class="text-sm text-muted-foreground">Vendors</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-primary">Free</p>
                    <p class="text-sm text-muted-foreground">Shipping*</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-primary">24/7</p>
                    <p class="text-sm text-muted-foreground">Support</p>
                </div>
            </div>

            <!-- Secondary links -->
            <div class="mt-16 flex flex-wrap justify-center gap-x-8 gap-y-4 text-sm text-muted-foreground">
                <Link href="/products" class="hover:text-foreground transition-colors">
                    {{ $t('nav.products') }}
                </Link>
                <Link href="/categories" class="hover:text-foreground transition-colors">
                    {{ $t('nav.categories') }}
                </Link>
                <Link href="/vendors" class="hover:text-foreground transition-colors">
                    {{ $t('nav.vendors') }}
                </Link>
            </div>
        </main>
    </div>
</template>
