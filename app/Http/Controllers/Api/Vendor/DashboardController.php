<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Return vendor dashboard data for API consumers (e.g. Vue Query).
     * Same shape as the Inertia Vendor Dashboard for consistency.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->attributes->get('vendor');

        $stats = [
            'total_products' => Product::where('vendor_id', $vendor->id)->count(),
            'active_products' => Product::where('vendor_id', $vendor->id)->where('status', 'active')->count(),
            'pending_orders' => OrderItem::where('vendor_id', $vendor->id)
                ->where('fulfillment_status', 'pending')
                ->count(),
            'total_revenue' => (float) OrderItem::where('vendor_id', $vendor->id)
                ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
                ->sum('vendor_payout'),
        ];

        $recentOrders = OrderItem::where('vendor_id', $vendor->id)
            ->with(['order:id,order_number,created_at,status', 'product:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'order_number' => $item->order->order_number,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'total' => (float) $item->total,
                'fulfillment_status' => $item->fulfillment_status,
                'created_at' => $item->created_at->toISOString(),
            ]);

        $lowStockProducts = Product::where('vendor_id', $vendor->id)
            ->where('track_inventory', true)
            ->where('status', 'active')
            ->whereRaw('stock_quantity <= low_stock_threshold')
            ->take(5)
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold']);

        return response()->json([
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
