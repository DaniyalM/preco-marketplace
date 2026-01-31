<?php

namespace App\Models\Concerns;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for models that belong to a user.
 * 
 * Automatically scopes queries to the current user
 * and sets keycloak_user_id on creation.
 */
trait BelongsToUser
{
    /**
     * Boot the trait
     */
    public static function bootBelongsToUser(): void
    {
        // Auto-set user_id on creation
        static::creating(function ($model) {
            if (!$model->keycloak_user_id && $model->shouldAutoSetUser()) {
                $model->keycloak_user_id = app(TenantContext::class)->getUserId();
            }
        });
    }

    /**
     * Whether to auto-set user on creation
     */
    protected function shouldAutoSetUser(): bool
    {
        return true;
    }

    /**
     * Scope to current user
     */
    public function scopeForCurrentUser(Builder $query): Builder
    {
        $userId = app(TenantContext::class)->getUserId();
        return $query->where($this->getTable() . '.keycloak_user_id', $userId);
    }

    /**
     * Scope to specific user
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where($this->getTable() . '.keycloak_user_id', $userId);
    }
}
