<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'keycloak_user_id',
        'session_id',
        'coupon_code',
    ];

    // Relationships
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Scopes
    public function scopeForUser($query, string $keycloakUserId)
    {
        return $query->where('keycloak_user_id', $keycloakUserId);
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // Cart operations
    public function addItem(int $productId, ?int $variantId = null, int $quantity = 1): CartItem
    {
        $existingItem = $this->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            return $existingItem->fresh();
        }

        return $this->items()->create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
    }

    public function updateItemQuantity(int $itemId, int $quantity): ?CartItem
    {
        $item = $this->items()->find($itemId);
        
        if (!$item) {
            return null;
        }

        if ($quantity <= 0) {
            $item->delete();
            return null;
        }

        $item->update(['quantity' => $quantity]);
        return $item->fresh();
    }

    public function removeItem(int $itemId): bool
    {
        return $this->items()->where('id', $itemId)->delete() > 0;
    }

    public function clear(): void
    {
        $this->items()->delete();
        $this->update(['coupon_code' => null]);
    }

    // Calculations
    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            return $item->getPrice() * $item->quantity;
        });
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getUniqueItemCountAttribute(): int
    {
        return $this->items->count();
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    // Guest to user migration
    public function migrateToUser(string $keycloakUserId): void
    {
        // Check if user already has a cart
        $existingCart = static::forUser($keycloakUserId)->first();

        if ($existingCart && $existingCart->id !== $this->id) {
            // Merge items into existing cart
            foreach ($this->items as $item) {
                $existingCart->addItem(
                    $item->product_id,
                    $item->product_variant_id,
                    $item->quantity
                );
            }
            $this->delete();
        } else {
            // Convert this cart to user cart
            $this->update([
                'keycloak_user_id' => $keycloakUserId,
                'session_id' => null,
            ]);
        }
    }

    // Vendor grouping
    public function getItemsByVendor(): \Illuminate\Support\Collection
    {
        return $this->items
            ->load('product.vendor')
            ->groupBy(fn ($item) => $item->product->vendor_id);
    }
}
