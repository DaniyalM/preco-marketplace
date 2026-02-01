<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\StatelessAuthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    public function index(Request $request)
    {
        $vendor = $this->authService->getVendor($request);

        if (!$vendor) {
            return redirect()->route('vendor.onboarding');
        }

        // Get orders that contain items from this vendor
        $orderIds = OrderItem::where('vendor_id', $vendor->id)
            ->distinct()
            ->pluck('order_id');

        $query = Order::whereIn('id', $orderIds)
            ->with([
                'items' => fn($q) => $q->where('vendor_id', $vendor->id),
            ]);

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ]),
                'vendor_subtotal' => $order->items->sum('subtotal'),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'created_at' => $order->created_at->toISOString(),
            ]);

        // Stats for this vendor
        $vendorOrderIds = $orderIds;
        $stats = [
            'total' => Order::whereIn('id', $vendorOrderIds)->count(),
            'pending' => Order::whereIn('id', $vendorOrderIds)->where('status', 'pending')->count(),
            'processing' => Order::whereIn('id', $vendorOrderIds)->where('status', 'processing')->count(),
            'shipped' => Order::whereIn('id', $vendorOrderIds)->where('status', 'shipped')->count(),
            'delivered' => Order::whereIn('id', $vendorOrderIds)->where('status', 'delivered')->count(),
            'total_revenue' => OrderItem::where('vendor_id', $vendor->id)
                ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
                ->sum('subtotal'),
        ];

        return Inertia::render('Vendor/Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $vendor = $this->authService->getVendor($request);

        // Verify this vendor has items in this order
        $hasItems = OrderItem::where('order_id', $order->id)
            ->where('vendor_id', $vendor->id)
            ->exists();

        if (!$hasItems) {
            abort(403, 'You do not have access to this order.');
        }

        $order->load([
            'items' => fn($q) => $q->where('vendor_id', $vendor->id)->with('product:id,name,slug'),
        ]);

        return Inertia::render('Vendor/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'shipping_address' => [
                    'city' => $order->shipping_city,
                    'state' => $order->shipping_state,
                    'country' => $order->shipping_country,
                ],
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_slug' => $item->product?->slug,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'fulfillment_status' => $item->fulfillment_status,
                ]),
                'vendor_subtotal' => $order->items->sum('subtotal'),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'created_at' => $order->created_at->toISOString(),
            ],
        ]);
    }

    public function updateItemFulfillment(Request $request, OrderItem $item)
    {
        $vendor = $this->authService->getVendor($request);

        // Verify this item belongs to this vendor
        if ($item->vendor_id !== $vendor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'fulfillment_status' => 'required|in:pending,processing,shipped,delivered',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $item->update($validated);

        return back()->with('success', 'Fulfillment status updated.');
    }
}
