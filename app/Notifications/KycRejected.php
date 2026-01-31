<?php

namespace App\Notifications;

use App\Models\VendorKyc;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VendorKyc $kyc,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Action Required: KYC Verification Update')
            ->greeting('Hello!')
            ->line('We were unable to verify your KYC documents.');

        if ($this->reason) {
            $message->line('**Reason:** ' . $this->reason);
        }

        return $message
            ->line('Please review the feedback and resubmit your documents.')
            ->action('Resubmit Documents', url('/vendor/onboarding/kyc'))
            ->line('If you need assistance, please contact our support team.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kyc_id' => $this->kyc->id,
            'status' => 'rejected',
            'reason' => $this->reason,
        ];
    }
}
