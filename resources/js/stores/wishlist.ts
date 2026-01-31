import { defineStore } from 'pinia';
import axios from 'axios';

interface WishlistItem {
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
    variant?: {
        id: number;
        display_name: string;
        price: number;
    } | null;
    added_at: string;
}

interface WishlistState {
    items: WishlistItem[];
    productIds: Set<number>;
    loading: boolean;
    error: string | null;
}

export const useWishlistStore = defineStore('wishlist', {
    state: (): WishlistState => ({
        items: [],
        productIds: new Set(),
        loading: false,
        error: null,
    }),

    getters: {
        count: (state) => state.items.length,
        
        isInWishlist: (state) => (productId: number) => {
            return state.productIds.has(productId);
        },
    },

    actions: {
        async fetchWishlist() {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get('/api/wishlist');
                this.items = response.data.data;
                this.productIds = new Set(this.items.map(item => item.product.id));
            } catch (err: any) {
                this.error = err.response?.data?.error || 'Failed to load wishlist';
            } finally {
                this.loading = false;
            }
        },

        async toggle(productId: number, variantId?: number) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.post('/api/wishlist', {
                    product_id: productId,
                    variant_id: variantId,
                });
                
                const inWishlist = response.data.in_wishlist;
                
                if (inWishlist) {
                    this.productIds.add(productId);
                } else {
                    this.productIds.delete(productId);
                    this.items = this.items.filter(item => item.product.id !== productId);
                }
                
                return inWishlist;
            } catch (err: any) {
                this.error = err.response?.data?.error || 'Failed to update wishlist';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async remove(productId: number) {
            this.loading = true;
            this.error = null;
            
            try {
                await axios.delete(`/api/wishlist/${productId}`);
                
                this.productIds.delete(productId);
                this.items = this.items.filter(item => item.product.id !== productId);
            } catch (err: any) {
                this.error = err.response?.data?.error || 'Failed to remove from wishlist';
                throw err;
            } finally {
                this.loading = false;
            }
        },
    },
});
