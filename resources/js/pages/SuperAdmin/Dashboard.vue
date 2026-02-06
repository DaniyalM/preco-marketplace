<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { SuperAdminLayout } from '@/components/layouts';
import { Button } from '@/components/ui';
</script>

<template>
    <SuperAdminLayout title="Super Admin">
        <Head title="Super Admin" />
        <h1 class="text-2xl font-semibold">Super Admin</h1>
        <Link href="/super-admin/marketplaces"><Button>Marketplaces</Button></Link>
    </SuperAdminLayout>
</template>
