<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'keycloak_user_id',
        'label',
        'first_name',
        'last_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default_shipping',
        'is_default_billing',
    ];

    protected $casts = [
        'is_default_shipping' => 'boolean',
        'is_default_billing' => 'boolean',
    ];

    // Scopes
    public function scopeForUser($query, string $keycloakUserId)
    {
        return $query->where('keycloak_user_id', $keycloakUserId);
    }

    public function scopeDefaultShipping($query)
    {
        return $query->where('is_default_shipping', true);
    }

    public function scopeDefaultBilling($query)
    {
        return $query->where('is_default_billing', true);
    }

    // Helpers
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

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

    public function getFormattedAddressAttribute(): array
    {
        return [
            'name' => $this->full_name,
            'phone' => $this->phone,
            'line1' => $this->address_line_1,
            'line2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
        ];
    }

    public function setAsDefaultShipping(): void
    {
        // Remove default from other addresses
        static::forUser($this->keycloak_user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default_shipping' => false]);
        
        $this->update(['is_default_shipping' => true]);
    }

    public function setAsDefaultBilling(): void
    {
        static::forUser($this->keycloak_user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default_billing' => false]);
        
        $this->update(['is_default_billing' => true]);
    }

    public function toOrderFormat(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
        ];
    }
}
