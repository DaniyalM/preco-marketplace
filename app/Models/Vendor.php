<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'keycloak_user_id',
        'email',
        'business_name',
        'slug',
        'business_type',
        'phone',
        'description',
        'logo',
        'banner',
        'website',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'status',
        'approved_at',
        'rejection_reason',
        'commission_rate',
        'is_featured',
        'settings',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'is_featured' => 'boolean',
        'settings' => 'array',
        'commission_rate' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Vendor $vendor) {
            if (empty($vendor->slug)) {
                $vendor->slug = Str::slug($vendor->business_name);
            }
        });
    }

    // Status checks
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    // Relationships
    public function kyc(): HasOne
    {
        return $this->hasOne(VendorKyc::class)->latest();
    }

    public function kycSubmissions(): HasMany
    {
        return $this->hasMany(VendorKyc::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePendingApproval($query)
    {
        return $query->whereIn('status', ['pending', 'under_review']);
    }

    // Helpers
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function suspend(string $reason): void
    {
        $this->update([
            'status' => 'suspended',
            'rejection_reason' => $reason,
        ]);
    }
}
