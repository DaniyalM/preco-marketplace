import { defineStore } from 'pinia';
import axios from 'axios';
import type { Cart, CartItem } from '@/types';

interface CartState {
    items: CartItem[];
    subtotal: number;
    itemCount: number;
    couponCode: string | null;
    loading: boolean;
    error: string | null;
}

export const useCartStore = defineStore('cart', {
    state: (): CartState => ({
        items: [],
        subtotal: 0,
        itemCount: 0,
        couponCode: null,
        loading: false,
        error: null,
    }),

    getters: {
        isEmpty: (state) => state.items.length === 0,
        
        uniqueItemCount: (state) => state.items.length,
        
        itemsByVendor: (state) => {
            const grouped: Record<string, CartItem[]> = {};
            // Group items by vendor (would need vendor info on product)
            return grouped;
        },
    },

    actions: {
        async fetchCart() {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get('/api/cart');
                const data = response.data.data;
                
                this.items = data.items;
                this.subtotal = data.subtotal;
                this.itemCount = data.item_count;
                this.couponCode = data.coupon_code;
            } catch (err: any) {
                this.error = err.response?.data?.error || 'Failed to load cart';
            } finally {
                this.loading = false;
            }
        },

        async addItem(productId: number, variantId?: number, quantity: number = 1) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.post('/api/cart/items', {
                    product_id: productId,
                    variant_id: variantId,
                    quantity,
                });
                
                const newItem = response.data.data;
                
                // Check if item already exists
                const existingIndex = this.items.findIndex(
                    item => item.product.id === productId && 
                           item.variant?.id === variantId
                );
                
                if (existingIndex >= 0) {
                    this.items[existingIndex] = newItem;
                } else {
                    this.items.push(newItem);
                }
                
                this.recalculate();
                
                return newItem;
            } catch (err: any) {
                this.error = err.response?.data?.error || 'Failed to add item';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async updateItemQuantity(itemId: number, quantity: number) {
            this.loading = true;
            this.error = null;
            
            try {
                if (quantity <= 0) {
                    await this.removeItem(itemId);
                    return;
                }
                
                const response = await axios.patch(`/api/cart/items/${itemId}`, {
                    quantity,
                });
                
                const updatedItem = response.data.data;
                
                if (updatedItem) {
                    const index = this.items.findIndex(item => item.id === itemId);
                    if (index >= 0) {
                        this.items[index] = updatedItem;
                    }
                } else {
                    // Item was removed
                    this.items = this.items.filter(item => item.id !== itemId);
                }
                
                this.recalculate();
            } catch (err: any) {
                this.error = err.response?.data?.error || 'Failed to update item';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async removeItem(itemId: number) {
            this.loading = true;
            this.error = null;
            
            try {
                await axios.delete(`/api/cart/items/${itemId}`);
                
                this.items = this.items.filter(item => item.id !== itemId);
                this.recalculate();
            } catch (err: any) {
                this.error = err.response?.data?.error || 'Failed to remove item';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async clearCart() {
            this.loading = true;
            this.error = null;
            
            try {
                await axios.delete('/api/cart');
                
                this.items = [];
                this.subtotal = 0;
                this.itemCount = 0;
                this.couponCode = null;
            } catch (err: any) {
                this.error = err.response?.data?.error || 'Failed to clear cart';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        recalculate() {
            this.subtotal = this.items.reduce(
                (sum, item) => sum + item.subtotal, 
                0
            );
            this.itemCount = this.items.reduce(
                (sum, item) => sum + item.quantity, 
                0
            );
        },
    },
});
