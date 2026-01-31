<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relationships
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Price helpers
    public function getPrice(): float
    {
        if ($this->variant) {
            return (float) $this->variant->price;
        }
        return (float) $this->product->base_price;
    }

    public function getCompareAtPrice(): ?float
    {
        if ($this->variant) {
            return $this->variant->compare_at_price ? (float) $this->variant->compare_at_price : null;
        }
        return $this->product->compare_at_price ? (float) $this->product->compare_at_price : null;
    }

    public function getSubtotalAttribute(): float
    {
        return $this->getPrice() * $this->quantity;
    }

    // Stock helpers
    public function isInStock(): bool
    {
        if ($this->variant) {
            return $this->variant->isInStock() && 
                   ($this->variant->stock_quantity >= $this->quantity || $this->variant->allow_backorder);
        }
        return $this->product->isInStock() && 
               ($this->product->stock_quantity >= $this->quantity || $this->product->allow_backorder);
    }

    public function getAvailableStock(): int
    {
        if ($this->variant) {
            return $this->variant->stock_quantity;
        }
        return $this->product->stock_quantity;
    }

    // Display helpers
    public function getDisplayNameAttribute(): string
    {
        $name = $this->product->name;
        if ($this->variant) {
            $name .= ' - ' . $this->variant->display_name;
        }
        return $name;
    }

    public function getOptionsAttribute(): array
    {
        if ($this->variant) {
            return $this->variant->option_values ?? [];
        }
        return [];
    }

    public function getImageAttribute(): ?string
    {
        if ($this->variant && $this->variant->images->isNotEmpty()) {
            return $this->variant->images->first()->url;
        }
        return $this->product->primary_image_url;
    }
}
