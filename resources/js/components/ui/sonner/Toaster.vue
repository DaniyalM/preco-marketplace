<script setup lang="ts">
import { useToastStore, type Toast, type ToastType } from '@/stores/toast';
import { storeToRefs } from 'pinia';

const toastStore = useToastStore();
const { toasts } = storeToRefs(toastStore);

const typeStyles: Record<ToastType, string> = {
    success: 'border-green-500/50 bg-green-50 text-green-900 dark:bg-green-950/90 dark:text-green-100',
    error: 'border-red-500/50 bg-red-50 text-red-900 dark:bg-red-950/90 dark:text-red-100',
    warning: 'border-amber-500/50 bg-amber-50 text-amber-900 dark:bg-amber-950/90 dark:text-amber-100',
    info: 'border-primary/50 bg-primary/10 text-foreground',
};

function remove(toast: Toast) {
    toastStore.remove(toast.id);
}
</script>

<template>
    <div
        class="fixed bottom-4 right-4 z-[100] flex max-h-[80vh] w-full max-w-sm flex-col gap-2 overflow-auto p-2"
        aria-live="polite"
    >
        <TransitionGroup name="toast" tag="div" class="flex flex-col gap-2">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="[
                    'flex items-center justify-between gap-3 rounded-lg border px-4 py-3 shadow-lg',
                    typeStyles[toast.type],
                ]"
            >
                <p class="text-sm font-medium">{{ toast.message }}</p>
                <button
                    type="button"
                    class="shrink-0 rounded p-1 opacity-70 transition-opacity hover:opacity-100"
                    aria-label="Dismiss"
                    @click="remove(toast)"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
