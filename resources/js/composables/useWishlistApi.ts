import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import {
    fetchWishlist,
    toggleWishlist,
    removeFromWishlist,
} from '@/api/wishlist';
import { queryKeys } from '@/queries/keys';

export function useWishlistQuery(options?: { enabled?: boolean }) {
    return useQuery({
        queryKey: queryKeys.wishlist(),
        queryFn: fetchWishlist,
        enabled: options?.enabled !== false,
    });
}

export function useToggleWishlistMutation() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({
            productId,
            variantId,
        }: {
            productId: number;
            variantId?: number;
        }) => toggleWishlist(productId, variantId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: queryKeys.wishlist() });
        },
    });
}

export function useRemoveFromWishlistMutation() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (productId: number) => removeFromWishlist(productId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: queryKeys.wishlist() });
        },
    });
}
