import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import {
    fetchCart,
    addCartItem,
    updateCartItem,
    removeCartItem,
    clearCart,
    type AddCartItemInput,
} from '@/api/cart';
import { queryKeys } from '@/queries/keys';

export function useCartQuery(options?: { enabled?: boolean }) {
    return useQuery({
        queryKey: queryKeys.cart(),
        queryFn: fetchCart,
        enabled: options?.enabled !== false,
    });
}

export function useAddCartItemMutation() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (input: AddCartItemInput) => addCartItem(input),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: queryKeys.cart() });
        },
    });
}

export function useUpdateCartItemMutation() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ itemId, quantity }: { itemId: number; quantity: number }) =>
            updateCartItem(itemId, quantity),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: queryKeys.cart() });
        },
    });
}

export function useRemoveCartItemMutation() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (itemId: number) => removeCartItem(itemId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: queryKeys.cart() });
        },
    });
}

export function useClearCartMutation() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: clearCart,
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: queryKeys.cart() });
        },
    });
}
