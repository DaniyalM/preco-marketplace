import { http } from './client';

export interface VendorDashboardStats {
    total_products: number;
    active_products: number;
    pending_orders: number;
    total_revenue: number;
}

export interface VendorDashboardOrder {
    id: number;
    order_number: string;
    product_name: string;
    quantity: number;
    total: number;
    fulfillment_status: string;
    created_at: string;
}

export interface VendorDashboardLowStock {
    id: number;
    name: string;
    sku: string;
    stock_quantity: number;
    low_stock_threshold: number;
}

export interface VendorDashboardData {
    stats: VendorDashboardStats;
    recentOrders: VendorDashboardOrder[];
    lowStockProducts: VendorDashboardLowStock[];
}

export async function fetchVendorDashboard(): Promise<VendorDashboardData> {
    const res = await http.get<VendorDashboardData>('/api/vendor/dashboard');
    return res.data;
}
