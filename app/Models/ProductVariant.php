<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'compare_at_price',
        'cost_price',
        'stock_quantity',
        'track_inventory',
        'allow_backorder',
        'weight',
        'length',
        'width',
        'height',
        'is_active',
        'sort_order',
        'option_values',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'track_inventory' => 'boolean',
        'allow_backorder' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'stock_quantity' => 'integer',
        'option_values' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProductVariant $variant) {
            if (empty($variant->sku)) {
                $variant->sku = strtoupper(Str::random(10));
            }
        });
    }

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_options',
            'product_variant_id',
            'product_option_value_id'
        );
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    // Stock helpers
    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }
        return $this->stock_quantity > 0 || $this->allow_backorder;
    }

    public function isLowStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }
        $threshold = $this->product->low_stock_threshold ?? 5;
        return $this->stock_quantity <= $threshold && $this->stock_quantity > 0;
    }

    public function decrementStock(int $quantity): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        if ($this->stock_quantity >= $quantity || $this->allow_backorder) {
            $this->decrement('stock_quantity', $quantity);
            return true;
        }

        return false;
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
    }

    // Price helpers
    public function hasDiscount(): bool
    {
        return $this->compare_at_price && $this->compare_at_price > $this->price;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->hasDiscount()) {
            return null;
        }
        return (int) round(100 - ($this->price / $this->compare_at_price * 100));
    }

    // Display helpers
    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if (is_array($this->option_values) && !empty($this->option_values)) {
            return implode(' / ', array_values($this->option_values));
        }

        return $this->sku;
    }

    public function getOptionSummaryAttribute(): string
    {
        if (is_array($this->option_values) && !empty($this->option_values)) {
            $parts = [];
            foreach ($this->option_values as $option => $value) {
                $parts[] = "{$option}: {$value}";
            }
            return implode(', ', $parts);
        }
        return '';
    }

    // Image helpers
    public function getPrimaryImageAttribute(): ?ProductImage
    {
        return $this->images->first() ?? $this->product->images->first();
    }
}
