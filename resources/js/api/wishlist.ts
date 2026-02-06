import { http } from './client';

export interface WishlistItemDto {
    id: number;
    product: {
        id: number;
        name: string;
        slug: string;
        primary_image_url?: string | null;
        base_price: number;
        compare_at_price?: number | null;
        is_in_stock: boolean;
    };
    variant?: { id: number; display_name: string; price: number } | null;
    added_at: string;
}

export async function fetchWishlist(): Promise<WishlistItemDto[]> {
    const res = await http.get<{ data: WishlistItemDto[] }>('/api/wishlist');
    return res.data.data;
}

export async function toggleWishlist(productId: number, variantId?: number) {
    const res = await http.post<{ in_wishlist: boolean }>('/api/wishlist', {
        product_id: productId,
        variant_id: variantId,
    });
    return res.data;
}

export async function removeFromWishlist(productId: number) {
    await http.delete(`/api/wishlist/${productId}`);
}
