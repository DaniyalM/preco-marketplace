<?php

namespace App\Jobs;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup abandoned/expired carts.
 * 
 * Scheduled to run periodically to free up reserved inventory
 * and clean database.
 */
class CleanupExpiredCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public int $daysOld = 30
    ) {
        $this->onQueue('cleanup');
    }

    public function handle(): void
    {
        Log::info("Starting cleanup of expired carts older than {$this->daysOld} days");

        $cutoffDate = now()->subDays($this->daysOld);

        // Get carts to delete with items
        $carts = Cart::where('updated_at', '<', $cutoffDate)
            ->with('items')
            ->cursor();

        $deletedCount = 0;
        $itemsCount = 0;

        foreach ($carts as $cart) {
            $itemsCount += $cart->items->count();
            $cart->items()->delete();
            $cart->delete();
            $deletedCount++;
        }

        Log::info("Completed cart cleanup", [
            'deleted_carts' => $deletedCount,
            'deleted_items' => $itemsCount,
        ]);
    }
}
