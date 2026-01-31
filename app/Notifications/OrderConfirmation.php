<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Order Confirmed: #{$this->order->order_number}")
            ->greeting("Thank you for your order!")
            ->line("Your order **#{$this->order->order_number}** has been confirmed.")
            ->line('**Order Summary:**');

        foreach ($this->order->items as $item) {
            $message->line("• {$item->product_name} x {$item->quantity} - $" . number_format($item->total, 2));
        }

        return $message
            ->line('**Total:** $' . number_format($this->order->total, 2))
            ->action('View Order', url("/orders/{$this->order->id}"))
            ->line('We will notify you when your order ships.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
        ];
    }
}
