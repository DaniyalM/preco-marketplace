<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'base_price',
        'compare_at_price',
        'cost_price',
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'allow_backorder',
        'has_variants',
        'product_type',
        'weight',
        'length',
        'width',
        'height',
        'status',
        'is_featured',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'view_count',
        'sold_count',
        'average_rating',
        'review_count',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'track_inventory' => 'boolean',
        'allow_backorder' => 'boolean',
        'has_variants' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'meta_keywords' => 'array',
        'average_rating' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'view_count' => 'integer',
        'sold_count' => 'integer',
        'review_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(6);
            }
            if (empty($product->sku)) {
                $product->sku = strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'id', 'product_id')
            ->where('is_primary', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePublished($query)
    {
        return $query->active()->whereNotNull('published_at');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_inventory', false)
              ->orWhere('stock_quantity', '>', 0)
              ->orWhere('allow_backorder', true);
        });
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Price helpers
    public function getCurrentPriceAttribute(): float
    {
        if ($this->has_variants && $this->variants->isNotEmpty()) {
            return (float) $this->variants->min('price');
        }
        return (float) $this->base_price;
    }

    public function getPriceRangeAttribute(): array
    {
        if ($this->has_variants && $this->variants->count() > 1) {
            return [
                'min' => (float) $this->variants->min('price'),
                'max' => (float) $this->variants->max('price'),
            ];
        }
        return [
            'min' => (float) $this->base_price,
            'max' => (float) $this->base_price,
        ];
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price && $this->compare_at_price > $this->base_price;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->hasDiscount()) {
            return null;
        }
        return (int) round(100 - ($this->base_price / $this->compare_at_price * 100));
    }

    // Stock helpers
    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        if ($this->has_variants) {
            return $this->variants->some(fn ($v) => $v->isInStock());
        }

        return $this->stock_quantity > 0 || $this->allow_backorder;
    }

    public function isLowStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }

        if ($this->has_variants) {
            return $this->variants->some(fn ($v) => $v->isLowStock());
        }

        return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

    public function getTotalStockAttribute(): int
    {
        if ($this->has_variants) {
            return $this->variants->sum('stock_quantity');
        }
        return $this->stock_quantity;
    }

    // Image helpers
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $image = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        return $image?->path;
    }

    // Status helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'active',
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'status' => 'inactive',
        ]);
    }

    // Rating helpers
    public function updateRatingStats(): void
    {
        $reviews = $this->approvedReviews;
        
        $this->update([
            'average_rating' => $reviews->avg('rating') ?? 0,
            'review_count' => $reviews->count(),
        ]);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
