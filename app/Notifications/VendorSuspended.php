<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorSuspended extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Vendor $vendor,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Important: Your Vendor Account Has Been Suspended')
            ->greeting("Hello, {$this->vendor->business_name}")
            ->line('Your vendor account has been suspended and is no longer able to process sales.');

        if ($this->reason) {
            $message->line('**Reason:** ' . $this->reason);
        }

        return $message
            ->line('If you believe this is an error or would like to appeal this decision, please contact our support team.')
            ->action('Contact Support', url('/support'))
            ->line('We take policy violations seriously to maintain a safe marketplace for all users.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'vendor_id' => $this->vendor->id,
            'status' => 'suspended',
            'reason' => $this->reason,
        ];
    }
}
