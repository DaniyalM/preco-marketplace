<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with([
            'items:id,order_id,product_id,product_name,quantity,unit_price',
        ]);

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhere('customer_email', 'like', "%{$request->search}%")
                  ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'items_count' => $order->items->count(),
                'subtotal' => (float) $order->subtotal,
                'shipping_cost' => (float) $order->shipping_cost,
                'tax_amount' => (float) $order->tax_amount,
                'discount_amount' => (float) $order->discount_amount,
                'total' => (float) $order->total,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at->toISOString(),
            ]);

        // Stats
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('status', '!=', 'cancelled')->sum('total'),
        ];

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $request->only(['status', 'payment_status', 'search', 'date_from', 'date_to']),
        ]);
    }

    public function show(Order $order)
    {
        $order->load([
            'items.product:id,name,slug',
            'items.variant',
        ]);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'shipping_address' => [
                    'line1' => $order->shipping_address_line1,
                    'line2' => $order->shipping_address_line2,
                    'city' => $order->shipping_city,
                    'state' => $order->shipping_state,
                    'postal_code' => $order->shipping_postal_code,
                    'country' => $order->shipping_country,
                ],
                'billing_address' => [
                    'line1' => $order->billing_address_line1,
                    'line2' => $order->billing_address_line2,
                    'city' => $order->billing_city,
                    'state' => $order->billing_state,
                    'postal_code' => $order->billing_postal_code,
                    'country' => $order->billing_country,
                ],
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant?->name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ]),
                'subtotal' => (float) $order->subtotal,
                'shipping_cost' => (float) $order->shipping_cost,
                'tax_amount' => (float) $order->tax_amount,
                'discount_amount' => (float) $order->discount_amount,
                'total' => (float) $order->total,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'shipping_method' => $order->shipping_method,
                'tracking_number' => $order->tracking_number,
                'notes' => $order->notes,
                'admin_notes' => $order->admin_notes,
                'created_at' => $order->created_at->toISOString(),
                'updated_at' => $order->updated_at->toISOString(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
            'notes' => 'nullable|string|max:1000',
        ]);

        $order->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['notes'] ?? $order->admin_notes,
        ]);

        // TODO: Send notification to customer

        return back()->with('success', 'Order status updated.');
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order->update(['payment_status' => $validated['payment_status']]);

        return back()->with('success', 'Payment status updated.');
    }

    public function updateTracking(Request $request, Order $order)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|max:100',
            'shipping_method' => 'nullable|string|max:100',
        ]);

        $order->update($validated);

        // Auto-update status to shipped if not already
        if ($order->status === 'processing') {
            $order->update(['status' => 'shipped']);
        }

        // TODO: Send shipping notification to customer

        return back()->with('success', 'Tracking information updated.');
    }

    public function addNote(Request $request, Order $order)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $existingNotes = $order->admin_notes ?? '';
        $newNote = '[' . now()->format('Y-m-d H:i') . '] ' . $validated['note'];
        
        $order->update([
            'admin_notes' => $existingNotes ? $existingNotes . "\n" . $newNote : $newNote,
        ]);

        return back()->with('success', 'Note added.');
    }
}
