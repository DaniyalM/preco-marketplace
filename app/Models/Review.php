<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'order_item_id',
        'keycloak_user_id',
        'customer_name',
        'rating',
        'title',
        'comment',
        'status',
        'is_verified_purchase',
        'admin_response',
        'helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForUser($query, string $keycloakUserId)
    {
        return $query->where('keycloak_user_id', $keycloakUserId);
    }

    // Status updates
    public function approve(): void
    {
        $this->update(['status' => 'approved']);
        $this->product->updateRatingStats();
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function respond(string $response): void
    {
        $this->update(['admin_response' => $response]);
    }

    // Helpers
    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }
}
