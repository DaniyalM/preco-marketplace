<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui';

interface Props {
    message?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <Head title="403 - Forbidden" />

        <div class="text-center">
            <p class="text-base font-semibold text-destructive">403</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-foreground sm:text-5xl">
                Access Denied
            </h1>
            <p class="mt-6 text-base leading-7 text-muted-foreground">
                {{ message || "Sorry, you don't have permission to access this page." }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
                <Link href="/">
                    <Button>Go back home</Button>
                </Link>
                <Link href="/help" class="text-sm font-semibold text-foreground hover:text-primary">
                    Contact support <span aria-hidden="true">&rarr;</span>
                </Link>
            </div>
        </div>
    </div>
</template>
