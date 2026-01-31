<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'path',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Helpers
    public function getUrlAttribute(): string
    {
        // If path is already a full URL, return as-is
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }
        
        // Otherwise, generate storage URL
        return asset('storage/' . $this->path);
    }

    public function makePrimary(): void
    {
        // Remove primary from other images of the same product
        static::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);
        
        $this->update(['is_primary' => true]);
    }
}
