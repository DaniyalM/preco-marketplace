<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'vendor_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'sku',
        'options',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'fulfillment_status',
        'tracking_number',
        'shipped_at',
        'commission_rate',
        'commission_amount',
        'vendor_payout',
    ];

    protected $casts = [
        'options' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vendor_payout' => 'decimal:2',
        'shipped_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (OrderItem $item) {
            // Calculate totals if not set
            if (empty($item->subtotal)) {
                $item->subtotal = $item->unit_price * $item->quantity;
            }
            if (empty($item->total)) {
                $item->total = $item->subtotal - ($item->discount_amount ?? 0) + ($item->tax_amount ?? 0);
            }
            
            // Calculate commission
            if (empty($item->commission_amount)) {
                $item->commission_amount = $item->total * ($item->commission_rate / 100);
                $item->vendor_payout = $item->total - $item->commission_amount;
            }
        });
    }

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Scopes
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopePendingFulfillment($query)
    {
        return $query->where('fulfillment_status', 'pending');
    }

    public function scopeShipped($query)
    {
        return $query->where('fulfillment_status', 'shipped');
    }

    // Status updates
    public function markAsProcessing(): void
    {
        $this->update(['fulfillment_status' => 'processing']);
    }

    public function ship(string $trackingNumber = null): void
    {
        $this->update([
            'fulfillment_status' => 'shipped',
            'tracking_number' => $trackingNumber,
            'shipped_at' => now(),
        ]);
        
        // Check if all items in order are shipped
        $order = $this->order;
        $allShipped = $order->items->every(fn ($item) => $item->fulfillment_status === 'shipped');
        
        if ($allShipped) {
            $order->ship();
        }
    }

    public function deliver(): void
    {
        $this->update(['fulfillment_status' => 'delivered']);
        
        // Check if all items in order are delivered
        $order = $this->order;
        $allDelivered = $order->items->every(fn ($item) => $item->fulfillment_status === 'delivered');
        
        if ($allDelivered) {
            $order->deliver();
        }
    }

    public function cancel(): void
    {
        $this->update(['fulfillment_status' => 'cancelled']);
        $this->restoreStock();
    }

    public function refund(): void
    {
        $this->update(['fulfillment_status' => 'refunded']);
    }

    // Stock management
    public function decrementStock(): void
    {
        if ($this->product_variant_id) {
            $this->variant?->decrementStock($this->quantity);
        } else {
            $this->product?->decrement('stock_quantity', $this->quantity);
        }
    }

    public function restoreStock(): void
    {
        if ($this->product_variant_id) {
            $this->variant?->incrementStock($this->quantity);
        } else {
            $this->product?->increment('stock_quantity', $this->quantity);
        }
    }

    // Helpers
    public function getDisplayNameAttribute(): string
    {
        $name = $this->product_name;
        if ($this->variant_name) {
            $name .= ' - ' . $this->variant_name;
        }
        return $name;
    }
}
