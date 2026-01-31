<?php

namespace App\Jobs;

use App\Models\Product;
use App\Notifications\LowStockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Send low stock alert to vendor.
 * 
 * Dispatched when product stock falls below threshold.
 */
class SendLowStockAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        public Product $product
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $this->product->load('vendor');

        if (!$this->product->vendor) {
            return;
        }

        Log::info("Sending low stock alert for product {$this->product->id} to vendor {$this->product->vendor_id}");

        Notification::route('mail', $this->product->vendor->email)
            ->notify(new LowStockAlert($this->product));
    }
}
