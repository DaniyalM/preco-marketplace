<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderForVendor extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public array $items
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $itemCount = count($this->items);
        $total = collect($this->items)->sum('total');

        $message = (new MailMessage)
            ->subject("🛒 New Order: #{$this->order->order_number}")
            ->greeting('You have a new order!')
            ->line("Order **#{$this->order->order_number}** contains {$itemCount} item(s) from your store.")
            ->line('**Items to fulfill:**');

        foreach ($this->items as $item) {
            $message->line("• {$item->product_name} x {$item->quantity}");
        }

        return $message
            ->line('**Your earnings:** $' . number_format($total, 2))
            ->action('View Order Details', url('/vendor/orders'))
            ->line('Please fulfill this order as soon as possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'item_count' => count($this->items),
        ];
    }
}
