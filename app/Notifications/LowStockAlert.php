<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠️ Low Stock Alert: {$this->product->name}")
            ->greeting('Low Stock Warning')
            ->line("Your product **{$this->product->name}** is running low on stock.")
            ->line("**Current stock:** {$this->product->stock_quantity}")
            ->line("**Low stock threshold:** {$this->product->low_stock_threshold}")
            ->action('Update Inventory', url("/vendor/products/{$this->product->id}/edit"))
            ->line('Restock soon to avoid missing sales!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'stock_quantity' => $this->product->stock_quantity,
        ];
    }
}
