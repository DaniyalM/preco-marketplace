<?php

namespace App\Jobs;

use App\Models\Vendor;
use App\Notifications\VendorApproved;
use App\Notifications\VendorRejected;
use App\Notifications\VendorSuspended;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Send notification to vendor about their approval status.
 * 
 * Offloaded to queue to not block admin actions and handle
 * potential email service delays.
 */
class SendVendorApprovalNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public Vendor $vendor,
        public string $action,
        public ?string $reason = null
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        Log::info("Sending {$this->action} notification to vendor {$this->vendor->id}");

        $notification = match ($this->action) {
            'approved' => new VendorApproved($this->vendor),
            'rejected' => new VendorRejected($this->vendor, $this->reason),
            'suspended' => new VendorSuspended($this->vendor, $this->reason),
            default => null,
        };

        if ($notification) {
            // Send to vendor email (using on-demand notification)
            Notification::route('mail', $this->vendor->email)
                ->notify($notification);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to send vendor notification", [
            'vendor_id' => $this->vendor->id,
            'action' => $this->action,
            'error' => $exception->getMessage(),
        ]);
    }
}
