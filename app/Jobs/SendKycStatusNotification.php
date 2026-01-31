<?php

namespace App\Jobs;

use App\Models\VendorKyc;
use App\Notifications\KycApproved;
use App\Notifications\KycRejected;
use App\Notifications\KycUnderReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Send KYC status update notification to vendor.
 */
class SendKycStatusNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public VendorKyc $kyc,
        public string $status,
        public ?string $reason = null
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $this->kyc->load('vendor');

        Log::info("Sending KYC {$this->status} notification to vendor {$this->kyc->vendor_id}");

        $notification = match ($this->status) {
            'approved' => new KycApproved($this->kyc),
            'rejected' => new KycRejected($this->kyc, $this->reason),
            'under_review' => new KycUnderReview($this->kyc),
            default => null,
        };

        if ($notification && $this->kyc->vendor) {
            Notification::route('mail', $this->kyc->vendor->email)
                ->notify($notification);
        }
    }
}
