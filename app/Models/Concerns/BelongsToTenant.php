<?php

namespace App\Models\Concerns;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for models that belong to a tenant.
 * 
 * Automatically scopes queries to the current tenant
 * and sets tenant_id on creation.
 */
trait BelongsToTenant
{
    /**
     * Boot the trait
     */
    public static function bootBelongsToTenant(): void
    {
        // Auto-set tenant_id on creation
        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = app(TenantContext::class)->getTenantId();
            }
        });

        // Global scope to filter by tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app(TenantContext::class)->getTenantId();
            
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });
    }

    /**
     * Scope to a specific tenant
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')
            ->where($this->getTable() . '.tenant_id', $tenantId);
    }

    /**
     * Query without tenant scope (for admin/cross-tenant queries)
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
