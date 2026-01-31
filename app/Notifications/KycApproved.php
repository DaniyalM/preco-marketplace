<?php

namespace App\Notifications;

use App\Models\VendorKyc;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VendorKyc $kyc
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ KYC Verification Approved')
            ->greeting('Hello!')
            ->line('Your KYC verification documents have been reviewed and approved.')
            ->line('Your vendor account is now fully verified and you can start selling.')
            ->action('Start Selling', url('/vendor'))
            ->line('Thank you for completing the verification process!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kyc_id' => $this->kyc->id,
            'status' => 'approved',
        ];
    }
}
