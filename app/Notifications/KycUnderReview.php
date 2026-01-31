<?php

namespace App\Notifications;

use App\Models\VendorKyc;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycUnderReview extends Notification implements ShouldQueue
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
            ->subject('KYC Verification In Progress')
            ->greeting('Hello!')
            ->line('Your KYC documents are now being reviewed by our team.')
            ->line('This process typically takes 1-2 business days.')
            ->line('We will notify you once the review is complete.')
            ->action('Check Status', url('/vendor/status'))
            ->line('Thank you for your patience!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kyc_id' => $this->kyc->id,
            'status' => 'under_review',
        ];
    }
}
