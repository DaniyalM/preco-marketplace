<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Vendor $vendor
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Your Vendor Account Has Been Approved!')
            ->greeting("Congratulations, {$this->vendor->business_name}!")
            ->line('Great news! Your vendor application has been reviewed and approved.')
            ->line('You can now start adding products and selling on our marketplace.')
            ->action('Go to Vendor Dashboard', url('/vendor'))
            ->line('Here are some tips to get started:')
            ->line('• Add your first products')
            ->line('• Complete your store profile')
            ->line('• Set up your payment information')
            ->line('Thank you for joining our marketplace!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'vendor_id' => $this->vendor->id,
            'status' => 'approved',
        ];
    }
}
