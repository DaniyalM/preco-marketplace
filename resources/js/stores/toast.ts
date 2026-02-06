import { defineStore } from 'pinia';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
    id: string;
    type: ToastType;
    message: string;
    createdAt: number;
}

interface ToastState {
    toasts: Toast[];
}

let id = 0;

export const useToastStore = defineStore('toast', {
    state: (): ToastState => ({
        toasts: [],
    }),

    actions: {
        add(type: ToastType, message: string): void {
            const toastId = `toast-${++id}`;
            this.toasts.push({
                id: toastId,
                type,
                message,
                createdAt: Date.now(),
            });
            setTimeout(() => this.remove(toastId), 5000);
        },

        remove(toastId: string): void {
            this.toasts = this.toasts.filter((t) => t.id !== toastId);
        },

        success(message: string): void {
            this.add('success', message);
        },

        error(message: string): void {
            this.add('error', message);
        },

        warning(message: string): void {
            this.add('warning', message);
        },

        info(message: string): void {
            this.add('info', message);
        },
    },
});
