<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui';

interface Props {
    title?: string;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth?.user;
</script>

<template>
    <div class="min-h-screen bg-muted/30">
        <header class="border-b bg-background">
            <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6">
                <nav class="flex items-center gap-6">
                    <Link href="/super-admin" class="font-semibold">Super Admin</Link>
                    <Link href="/super-admin/marketplaces" class="text-sm text-muted-foreground hover:text-foreground">Marketplaces</Link>
                </nav>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-muted-foreground">{{ user?.name ?? user?.email }}</span>
                    <Link href="/auth/logout">
                        <Button variant="ghost" size="sm">Sign out</Button>
                    </Link>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
            <slot></slot>
        </main>
    </div>
</template>
