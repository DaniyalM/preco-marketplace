<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\OrderConfirmation;
use App\Notifications\NewOrderForVendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Process a newly placed order.
 * 
 * - Send confirmation email to customer
 * - Notify vendors about their items
 * - Update inventory
 * - Trigger analytics
 */
class ProcessOrderPlaced implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(
        public Order $order
    ) {
        $this->onQueue('orders');
    }

    public function handle(): void
    {
        Log::info("Processing order {$this->order->order_number}");

        $this->order->load(['items.vendor', 'items.product', 'items.variant']);

        // 1. Send confirmation to customer
        $this->sendCustomerConfirmation();

        // 2. Notify each vendor about their items
        $this->notifyVendors();

        // 3. Update inventory
        $this->updateInventory();

        // 4. Update product sales counts
        $this->updateSalesCounts();

        Log::info("Completed processing order {$this->order->order_number}");
    }

    protected function sendCustomerConfirmation(): void
    {
        if ($this->order->customer_email) {
            Notification::route('mail', $this->order->customer_email)
                ->notify(new OrderConfirmation($this->order));
        }
    }

    protected function notifyVendors(): void
    {
        // Group items by vendor
        $vendorItems = $this->order->items->groupBy('vendor_id');

        foreach ($vendorItems as $vendorId => $items) {
            $vendor = $items->first()->vendor;
            
            if ($vendor && $vendor->email) {
                Notification::route('mail', $vendor->email)
                    ->notify(new NewOrderForVendor($this->order, $items->all()));
            }
        }
    }

    protected function updateInventory(): void
    {
        foreach ($this->order->items as $item) {
            if ($item->variant) {
                $item->variant->decrement('stock_quantity', $item->quantity);
            } elseif ($item->product && $item->product->track_inventory) {
                $item->product->decrement('stock_quantity', $item->quantity);
            }
        }
    }

    protected function updateSalesCounts(): void
    {
        foreach ($this->order->items as $item) {
            if ($item->product) {
                $item->product->increment('sold_count', $item->quantity);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessOrderPlaced failed for order {$this->order->order_number}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
