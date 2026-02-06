import type { Cart } from '@/types';
import { getData, http } from './client';

export interface CartData {
    items: Cart['items'];
    subtotal: number;
    item_count: number;
    coupon_code?: string | null;
}

export async function fetchCart(): Promise<CartData> {
    const res = await http.get<{ data: CartData }>('/api/cart');
    return res.data.data;
}

export interface AddCartItemInput {
    product_id: number;
    variant_id?: number;
    quantity?: number;
}

export async function addCartItem(input: AddCartItemInput) {
    const res = await http.post<{ data: unknown }>('/api/cart/items', input);
    return res.data;
}

export async function updateCartItem(itemId: number, quantity: number) {
    const res = await http.patch<{ data: unknown }>(
        `/api/cart/items/${itemId}`,
        { quantity }
    );
    return res.data;
}

export async function removeCartItem(itemId: number) {
    await http.delete(`/api/cart/items/${itemId}`);
}

export async function clearCart() {
    await http.delete('/api/cart');
}
