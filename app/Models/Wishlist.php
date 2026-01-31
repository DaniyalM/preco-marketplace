<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'keycloak_user_id',
        'product_id',
        'product_variant_id',
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

    // Scopes
    public function scopeForUser($query, string $keycloakUserId)
    {
        return $query->where('keycloak_user_id', $keycloakUserId);
    }

    // Static helpers
    public static function toggle(string $userId, int $productId, ?int $variantId = null): bool
    {
        $existing = static::forUser($userId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false; // Removed from wishlist
        }

        static::create([
            'keycloak_user_id' => $userId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
        ]);

        return true; // Added to wishlist
    }

    public static function isInWishlist(string $userId, int $productId, ?int $variantId = null): bool
    {
        return static::forUser($userId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->exists();
    }
}
