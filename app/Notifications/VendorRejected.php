<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorRejected extends Notification implements ShouldQueue
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
            ->subject('Update on Your Vendor Application')
            ->greeting("Hello, {$this->vendor->business_name}")
            ->line('We have reviewed your vendor application and unfortunately, we are unable to approve it at this time.');

        if ($this->reason) {
            $message->line('**Reason:** ' . $this->reason);
        }

        return $message
            ->line('You can update your application and resubmit for review.')
            ->action('Update Application', url('/vendor/onboarding'))
            ->line('If you have any questions, please contact our support team.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'vendor_id' => $this->vendor->id,
            'status' => 'rejected',
            'reason' => $this->reason,
        ];
    }
}
