<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useLocale } from '@/composables/useLocale';
import { Button } from '@/components/ui';

const { t } = useLocale();
</script>

<template>
    <Head :title="t('welcome.title')" />
    <div
        class="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-slate-950 dark:to-slate-900 text-slate-900 dark:text-slate-100"
    >
        <!-- Simple header -->
        <header class="border-b border-slate-200/60 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm sticky top-0 z-10">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
                <span class="font-semibold text-lg">{{ t('welcome.title') }}</span>
                <div class="flex items-center gap-3">
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
                <p class="text-sm font-medium text-primary">{{ t('welcome.tagline') }}</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    {{ t('welcome.heroTitle') }}
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground">
                    {{ t('welcome.heroSubtitle') }}
                </p>
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <Link href="/products">
                        <Button size="lg" class="min-w-[160px]">
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
